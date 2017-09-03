<?php

namespace Phpactor\ContextAction\Action;

use Phpactor\ContextAction\Action;
use Phpactor\ContextAction\Result;
use Phpactor\WorseReflection\Core\Reflection\Inference\SymbolInformation;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\Reflection\Inference\Symbol;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\ContextAction\Result\FailureResult;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\ContextAction\Result\GotoDefinitionResult;

class GotoDefinitionAction implements Action
{
    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function perform(SymbolInformation $symbolInformation): Result
    {
        switch ($symbolInformation->symbol()->symbolType()) {
            case Symbol::METHOD:
            case Symbol::PROPERTY:
            case Symbol::CONSTANT:
                return $this->gotoMember($symbolInformation);
        }

        return FailureResult::withReason('Do not know how to goto definition of symbol type "%s"', $symbolInformation->symbol()->symbolType());
    }

    private function gotoMember(SymbolInformation $symbolInformation)
    {
        $symbolName = $symbolInformation->symbol()->name();
        $symbolType = $symbolInformation->symbol()->symbolType();

        if (null === $symbolInformation->classType()) {
            return FailureResult::withReason('Containing class for member "%s" could not be determined', $symbolName);
        }

        try {
            $containingClass = $this->reflector->reflectClassLike(ClassName::fromString((string) $symbolInformation->classType()));
        } catch (NotFound $e) {
            return FailureResult::withReason($e->getMessage());
        }

        if ($symbolType === Symbol::PROPERTY && $containingClass->isInterface()) {
            return FailureResult::withReason(sprintf('Symbol is a property and class "%s" is an interface', (string) $containingClass->name()));
        }

        $path = $containingClass->sourceCode()->path();

        if (null === $path) {
            return FailureResult::withReason(sprintf(
                'The source code for class "%s" has no path associated with it.',
                (string) $containingClass->name()
            ));
        }

        switch ($symbolType) {
            case Symbol::METHOD:
                $members = $containingClass->methods();
                break;
            case Symbol::CONSTANT:
                $members = $containingClass->constants();
                break;
            case Symbol::PROPERTY:
                $members = $containingClass->properties();
                break;
        }

        if (false === $members->has($symbolName)) {
            return FailureResult::withReason(sprintf(
                'Class "%s" has no %s named "%s", has: "%s"',
                $containingClass->name(), $symbolType, $symbolName, implode('", "', $members->keys())
            ));
        }

        $member = $members->get($symbolName);

        return GotoDefinitionResult::fromClassPathAndOffset(
            $path,
            $member->position()->start()
        );
    }
}

