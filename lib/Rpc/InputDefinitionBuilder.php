<?php

namespace Phpactor\Rpc;

use Phpactor\Rpc\Response\Input\Input;
use Phpactor\Rpc\InputDefinition;

class InputDefinitionBuilder
{
    private $requiredInputs = [];
    private $requiredParameters = [];
    private $defaults = [];

    public function requireInput(Input $input): InputDefinitionBuilder
    {
        $this->requiredInputs[$input->name()] = $input;

        return $this;
    }

    public function requireParameter(string $name): InputDefinitionBuilder
    {
        $this->requiredParameters[] = $name;

        return $this;
    }

    public function setDefault(string $name, string $value): InputDefinitionBuilder
    {
        $this->defaults[$name] = $value;

        return $this;
    }

    public function build()
    {
        return new InputDefinition(
            $this->requiredInputs,
            $this->requiredParameters,
            $this->defaults
        );
    }
}
