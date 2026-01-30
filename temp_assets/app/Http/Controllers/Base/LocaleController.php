<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\JsonResponse;
use Illuminate\Translation\Translator;
use Illuminate\Contracts\Translation\Loader;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Base\LocaleRequest;

class LocaleController extends Controller
{
    protected Loader $loader;

    public function __construct(Translator $translator)
    {
        $this->loader = $translator->getLoader();
    }

    
    public function __invoke(LocaleRequest $request): JsonResponse
    {
        $locale = $request->input('locale');
        $namespace = $request->input('namespace');
        $response[$locale][$namespace] = $this->i18n($this->loader->load($locale, $namespace));

        return new JsonResponse($response, 200, [
            
            
            
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'ETag' => md5(json_encode($response, JSON_THROW_ON_ERROR)),
        ]);
    }

    
    protected function i18n(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->i18n($value);
            } else {
                
                
                
                
                
                
                
                
                
                $data[$key] = preg_replace('/:([\w.-]+\w)([^\w:]?|$)/m', '{{$1}}$2', $value);
            }
        }

        return $data;
    }
}
