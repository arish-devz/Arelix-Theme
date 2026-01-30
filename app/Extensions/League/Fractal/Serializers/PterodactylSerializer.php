<?php

namespace Pterodactyl\Extensions\League\Fractal\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class PterodactylSerializer extends ArraySerializer
{
    
    public function item(?string $resourceKey, array $data): array
    {
        return [
            'object' => $resourceKey,
            'attributes' => $data,
        ];
    }

    
    public function collection(?string $resourceKey, array $data): array
    {
        $response = [];
        foreach ($data as $datum) {
            $response[] = $this->item($resourceKey, $datum);
        }

        return [
            'object' => 'list',
            'data' => $response,
        ];
    }

    
    public function null(): ?array
    {
        return [
            'object' => 'null_resource',
            'attributes' => null,
        ];
    }

    
    public function mergeIncludes(array $transformedData, array $includedData): array
    {
        foreach ($includedData as $key => $datum) {
            $transformedData['relationships'][$key] = $datum;
        }

        return $transformedData;
    }
}
