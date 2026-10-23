<div class="relative"
     x-data="{ open: false, active: -1, options() { return this.$root.querySelectorAll('[role=option]') } }"
     x-on:click.outside="open = false"
     role="combobox">
  <div class="join w-full">
    <input type="search" name="q[title_or_description_cont]"
        placeholder="タイトルか説明文で検索..."
        class="input input-bordered join-item w-full"
        wire:model.live.debounce.300ms="query"
        x-on:input="open = true; active = -1"
        x-on:keydown.escape="open = false"
        x-on:keydown.arrow-down.prevent="active = Math.min(active + 1, options().length - 1)"
        x-on:keydown.arrow-up.prevent="active = Math.max(active - 1, 0)"
        x-on:keydown.enter="if (open && active >= 0) { $event.preventDefault(); options()[active]?.click() }"
        autocomplete="off">
    <input type="submit" value="検索" class="btn btn-primary join-item">
  </div>
  @if ($antiHabits->isNotEmpty())
    <ul class="list-group absolute z-10 w-full top-full mt-1 max-h-60 overflow-y-auto bg-base-100 shadow-lg rounded-lg border border-base-300"
        x-show="open">
      @foreach ($antiHabits as $antiHabit)
        <li wire:key="suggestion-{{ $antiHabit->id }}"
            class="list-group-item px-4 py-2 cursor-pointer"
            role="option"
            x-bind:class="active === {{ $loop->index }} ? 'bg-primary text-primary-content font-semibold' : 'hover:bg-base-200'"
            wire:click="select(@js($antiHabit->title))"
            x-on:click="open = false">
          {{ $antiHabit->title }}
        </li>
      @endforeach
    </ul>
  @endif
</div>
