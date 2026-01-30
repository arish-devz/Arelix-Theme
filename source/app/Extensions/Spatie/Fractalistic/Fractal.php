<?php

namespace Pterodactyl\Extensions\Spatie\Fractalistic;

use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;
use Spatie\Fractal\Fractal as SpatieFractal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Extensions\League\Fractal\Serializers\PterodactylSerializer;

class Fractal extends SpatieFractal
{
    
    public function createData(): Scope
    {
        
        if (is_null($this->serializer)) {
            $this->serializer = new PterodactylSerializer();
        }

        
        
        if (is_null($this->paginator) && $this->data instanceof LengthAwarePaginator) {
            $this->paginator = new IlluminatePaginatorAdapter($this->data);
        }

        
        
        if (
            is_null($this->resourceName)
            && $this->transformer instanceof TransformerAbstract
            && method_exists($this->transformer, 'getResourceName')
        ) {
            $this->resourceName = $this->transformer->getResourceName();
        }

        return parent::createData();
    }
}
