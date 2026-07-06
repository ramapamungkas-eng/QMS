<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppBrand extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <a href="/" wire:navigate>
                    <div {{ $attributes->class(["hidden-when-collapsed"]) }}>
                        <div class="flex flex-col items-start gap-0 w-fit px-2">
                            <div class="flex items-center gap-2.5">
                                <x-icon name="o-cog" class="w-5 h-5" />
                                <div>
                                    <span class="block text-lg font-bold tracking-tight leading-none">基準 QJUN</span>
                                    <span class="block text-[10px] font-medium tracking-wider uppercase leading-tight mt-0.5 opacity-50">Quality Management System</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="display-when-collapsed hidden mx-5 mt-5 mb-1">
                        <x-icon name="o-cog" class="w-5 h-5" />
                    </div>
                </a>
            HTML;
    }
}
