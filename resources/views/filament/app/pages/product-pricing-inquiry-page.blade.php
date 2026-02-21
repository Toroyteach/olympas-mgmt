<x-filament-panels::page>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Products Table --}}
        <div class="lg:col-span-2">
            {{ $this->table }}
        </div>

        {{-- Quote Sidebar --}}
        <div class="space-y-4">

            {{-- Quote Cart --}}
            <x-filament::section>
                <x-slot name="heading">
                    Price Quote
                    @if($this->getCartCount() > 0)
                        <x-filament::badge color="primary" class="ml-2">
                            {{ $this->getCartCount() }}
                        </x-filament::badge>
                    @endif
                </x-slot>

                @if(empty($cart))
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No items added yet. Click "Add to Quote" on any product.
                    </p>
                @else
                    <div class="space-y-3">
                        @foreach($cart as $key => $item)
                            <div class="flex items-start justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $item['name'] }}
                                    </p>
                                    @if($item['sku'])
                                        <p class="text-xs text-gray-500">{{ $item['sku'] }}</p>
                                    @endif
                                    <p class="text-xs text-primary-600 dark:text-primary-400 mt-1">
                                        KES {{ number_format($item['price'], 2) }} × {{ $item['quantity'] }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 ml-2">
                                    <input
                                        type="number"
                                        min="1"
                                        value="{{ $item['quantity'] }}"
                                        wire:change="updateQuantity('{{ $key }}', $event.target.value)"
                                        class="w-14 rounded border border-gray-300 px-1.5 py-0.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    />
                                    <button
                                        wire:click="removeFromCart('{{ $key }}')"
                                        class="text-danger-500 hover:text-danger-700"
                                        title="Remove"
                                    >
                                        <x-filament::icon name="trash" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <div class="flex justify-between text-sm font-semibold">
                            <span>Total</span>
                            <span class="text-primary-600 dark:text-primary-400">
                                KES {{ number_format($this->getCartTotal(), 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <x-filament::button
                            wire:click="generateQuote"
                            size="sm"
                            class="flex-1"
                        >
                            Generate Quote
                        </x-filament::button>
                        <x-filament::button
                            wire:click="clearCart"
                            size="sm"
                            color="danger"
                            outlined
                        >
                            Clear
                        </x-filament::button>
                    </div>
                @endif
            </x-filament::section>

            {{-- Generated Quote --}}
            @if($quoteData)
                <x-filament::section>
                    <x-slot name="heading">
                        <span class="text-success-600">Quote: {{ $quoteData['reference'] }}</span>
                    </x-slot>

                    <p class="text-xs text-gray-500 mb-3">Generated: {{ $quoteData['generated'] }}</p>

                    <div class="space-y-2">
                        @foreach($quoteData['items'] as $item)
                            <div class="flex justify-between text-sm">
                                <span>{{ $item['name'] }} × {{ $item['quantity'] }}</span>
                                <span class="font-medium">
                                    KES {{ number_format($item['price'] * $item['quantity'], 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                        <div class="flex justify-between font-bold">
                            <span>Grand Total</span>
                            <span class="text-primary-600">
                                KES {{ number_format($quoteData['total'], 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <x-filament::button
                            tag="button"
                            onclick="window.print()"
                            size="sm"
                            outlined
                            class="w-full"
                        >
                            <x-filament::icon name="printer" class="h-4 w-4 mr-1" />
                            Print Quote
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endif

        </div>
    </div>

</x-filament-panels::page>
