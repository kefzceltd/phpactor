<?php

namespace Phpactor\Rpc\Handler;

use Phpactor\Rpc\Handler;
use Phpactor\Application\ClassReferences;
use Phpactor\Container\SourceCodeFilesystemExtension;
use Phpactor\Rpc\Editor\ReturnAction;
use Phpactor\Rpc\Editor\ReturnOption;
use Phpactor\Rpc\Editor\ReturnChoiceAction;
use Phpactor\Rpc\Editor\EchoAction;
use Phpactor\Rpc\Editor\FileReferencesAction;
use Phpactor\Rpc\Editor\StackAction;
use Phpactor\Application\ClassMethodReferences;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\Reflection\Inference\Symbol;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\Offset;
use Phpactor\WorseReflection\Core\Reflection\Inference\SymbolInformation;
use Phpactor\Rpc\Editor\Input\ChoiceInput;
use Phpactor\Rpc\Editor\InputCallbackAction;
use Phpactor\Rpc\ActionRequest;
use Phpactor\Filesystem\Domain\FilesystemRegistry;

class ReferencesHandler implements Handler
{
    /**
     * @var ClassReferences
     */
    private $classReferences;

    /**
     * @var string
     */
    private $defaultFilesystem;

    /**
     * @var ClassMethodReferences
     */
    private $classMethodReferences;

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var FilesystemRegistry
     */
    private $filesystemRegistry;

    public function __construct(
        Reflector $reflector,
        ClassReferences $classReferences,
        ClassMethodReferences $classMethodReferences,
        FilesystemRegistry $filesystemRegistry,
        string $defaultFilesystem = SourceCodeFilesystemExtension::FILESYSTEM_GIT
    ) {
        $this->classReferences = $classReferences;
        $this->defaultFilesystem = $defaultFilesystem;
        $this->classMethodReferences = $classMethodReferences;
        $this->reflector = $reflector;
        $this->filesystemRegistry = $filesystemRegistry;
    }

    public function name(): string
    {
        return 'references';
    }

    public function defaultParameters(): array
    {
        return [
            'offset' => null,
            'source' => null,
            'filesystem' => null,
            'risky' => null,
        ];
    }

    public function handle(array $arguments)
    {
        $filesystem = $this->defaultFilesystem;
        $offset = $this->reflector->reflectOffset(SourceCode::fromString($arguments['source']), Offset::fromInt($arguments['offset']));
        $symbolInformation = $offset->symbolInformation();

        if (!in_array($symbolInformation->symbol()->symbolType(), [ Symbol::CLASS_, Symbol::METHOD ])) {
            throw new \RuntimeException(sprintf(
                'Unsupported symbol type at offset %s: "%s"',
                $arguments['offset'],
                $symbolInformation->symbol()->symbolType()
            ));
        }

        $missingInputs = [];
        if (null === $arguments['filesystem']) {
            $missingInputs[] = ChoiceInput::fromNameLabelChoicesAndDefault(
                'filesystem',
                sprintf('Find references to "%s" in:', $symbolInformation->symbol()->name()),
                array_combine($this->filesystemRegistry->names(), $this->filesystemRegistry->names()),
                $this->defaultFilesystem
            );
        }

        if (null === $arguments['risky'] && $symbolInformation->symbol()->symbolType() === Symbol::METHOD) {
            $missingInputs[] = ChoiceInput::fromNameLabelChoicesAndDefault(
                'risky',
                'Include risky methods?:',
                [
                    'Yes' => true,
                    'No' => false,
                ],
                false
            );
        }

        if ($missingInputs) {
            return InputCallbackAction::fromCallbackAndInputs(
                ActionRequest::fromNameAndParameters(
                    $this->name(),
                    $arguments
                ),
                $missingInputs
            );
        }

        $references = $this->getReferences($arguments['filesystem'], $symbolInformation, $arguments['risky']);

        if (count($references) === 0) {
            return EchoAction::fromMessage('No references found');
        }

        $count = array_reduce($references, function ($count, $result) {
            $count += count($result['references']);
            return $count;
        }, 0);

        $riskyCount = array_reduce($references, function ($count, $result) {
            if (!isset($result['risky_references'])) {
                return $count;
            }

            $count += count($result['risky_references']);
            return $count;
        }, 0);

        $riskyMessage = '';
        if ($symbolInformation->symbol()->symbolType() === Symbol::METHOD) {
            $riskyMessage .= sprintf('in class "%s"', (string) $symbolInformation->classType());
        }

        if (null !== $arguments['risky']) {
            $riskyMessage .= sprintf('(%s risky%s) ', $riskyCount, ($arguments['risky'] ? ', listing' : ', ignoring'));
        }

        return StackAction::fromActions([
            EchoAction::fromMessage(sprintf(
                'Found %s references to %s "%s" %susing FS "%s"',
                $count,
                $symbolInformation->symbol()->symbolType(),
                $symbolInformation->symbol()->name(),
                $riskyMessage,
                $arguments['filesystem']
            )),
            FileReferencesAction::fromArray($references)
        ]);
    }

    private function classReferences(string $filesystem, SymbolInformation $symbolInformation)
    {
        $classType = (string) $symbolInformation->type();

        $references = $this->classReferences->findReferences($filesystem, $classType);
        return $references['references'];
    }

    private function methodReferences(string $filesystem, SymbolInformation $symbolInformation, bool $risky = null)
    {
        $classType = (string) $symbolInformation->classType();
        $references = $this->classMethodReferences->findOrReplaceReferences($filesystem, $classType, $symbolInformation->symbol()->name());

        if (false === $risky) {
            return $references['references'];
        }

        return array_map(function (array $reference) {
            foreach ($reference['risky_references'] as $riskyReference) {
                $reference['references'][] = $riskyReference;
            }

            return $reference;
        }, $references['references']);
    }

    private function getReferences(string $filesystem, SymbolInformation $symbolInformation, bool $risky = null)
    {
        switch ($symbolInformation->symbol()->symbolType()) {
            case Symbol::CLASS_:
                return $this->classReferences($filesystem, $symbolInformation);
            case Symbol::METHOD:
                return $this->methodReferences($filesystem, $symbolInformation, $risky);
        }

        throw new \RuntimeException(sprintf(
            'Cannot find references for symbol type "%s"',
            $symbolInformation->symbol()->symbolType()
        ));
    }
}

