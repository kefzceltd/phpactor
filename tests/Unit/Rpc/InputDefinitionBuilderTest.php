<?php

namespace Phpactor\Tests\Unit\Rpc;

use PHPUnit\Framework\TestCase;
use Phpactor\Rpc\InputDefinitionBuilder;
use Phpactor\Rpc\Response\Input\TextInput;
use Phpactor\Rpc\InputDefinition;

class InputDefinitionBuilderTest extends TestCase
{
    public function testRequireInput()
    {
        $definition = $this->createBuilder()
            ->requireInput(TextInput::fromName('one'))
            ->requireInput(TextInput::fromName('two'))
            ->build();

        $this->assertEquals(
            new InputDefinition([
                'one' => TextInput::fromName('one'),
                'two' => TextInput::fromName('two'),
            ]),
            $definition
        );
    }

    public function testRequireParameters()
    {
        $definition = $this->createBuilder()
            ->requireParameter('one')
            ->requireParameter('two')
            ->build();

        $this->assertEquals(
            new InputDefinition([], ['one', 'two']),
            $definition
        );
    }

    public function testDefaults()
    {
        $definition = $this->createBuilder()
            ->setDefault('one', 'two')
            ->setDefault('three', 'four')
            ->build();

        $this->assertEquals(
            new InputDefinition([], [], [ 'one' => 'two', 'three' => 'four' ]),
            $definition
        );
    }

    private function createBuilder(): InputDefinitionBuilder
    {
        return new InputDefinitionBuilder();
    }
}
