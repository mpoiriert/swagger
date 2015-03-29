<?php

namespace Draw\Swagger;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
class GenerateEvent extends GenericEvent
{
    const NAME = "draw_swagger.generate";

    /**
     * @return \Draw\Swagger\Schema\Swagger
     */
    public function getSwagger()
    {
        return $this['swagger'];
    }
} 