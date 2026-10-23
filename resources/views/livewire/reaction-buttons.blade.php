<div class="inline">
  @foreach ($kinds as $item)
    <button type="button"
            wire:key="reaction-{{ $item['kind']->key() }}"
            wire:click="toggle('{{ $item['kind']->key() }}')"
            class="badge {{ $item['reacted'] ? 'badge-outline badge-primary' : 'badge-dash badge-base' }}">
      {{ $item['kind']->emoji() }}
      {{ $item['count'] }}
    </button>
  @endforeach
</div>
