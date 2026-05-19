<?php

namespace App\Livewire\Layout;

use Livewire\Component;

class HelpDrawer extends Component
{
    public string $routeName = '';

    public function mount(): void
    {
        $this->routeName = request()->route()?->getName() ?? '';
    }

    public function render()
    {
        $routeMap = config('help.route_map', []);
        $pageKey  = $routeMap[$this->routeName] ?? $this->routeName;
        $pages    = config('help.pages', []);
        $page     = $pages[$pageKey] ?? null;
        $guides   = collect(config('help.guides', []))
                        ->filter(fn($g) => in_array($pageKey, $g['modulos'] ?? []))
                        ->values();

        return view('livewire.layout.help-drawer', [
            'page'   => $page,
            'guides' => $guides,
        ]);
    }
}
