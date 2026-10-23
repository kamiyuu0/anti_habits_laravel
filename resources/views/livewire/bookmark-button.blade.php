<div class="inline">
  @if ($bookmarked)
    <button type="button" wire:click="toggle" class="btn btn-primary btn-sm">
      <i class="fas fa-bookmark"></i>
    </button>
  @else
    <button type="button" wire:click="toggle" class="btn btn-ghost btn-sm">
      <i class="far fa-bookmark"></i>
    </button>
  @endif
</div>
