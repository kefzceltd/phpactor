<?php

namespace Phpactor\Rpc;

class InputDefinition
{
    /**
     * @var array
     */
    private $requiredInputs;

    /**
     * @var array
     */
    private $requiredParameters;

    /**
     * @var array
     */
    private $defaults;

    public function __construct(
        array $requiredInputs = [],
        array $requiredParameters = [],
        array $defaults = []
    )
    {
        $this->requiredInputs = $requiredInputs;
        $this->requiredParameters = $requiredParameters;
        $this->defaults = $defaults;
    }
}
