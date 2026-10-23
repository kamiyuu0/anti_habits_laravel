<div>
  <h2 class="card-title text-2xl mb-6">
    応援メッセージ
    <div class="badge badge-neutral">
      <span>{{ $antiHabit->comments_count }}</span>件
    </div>
  </h2>

  <!-- 新規コメントフォーム -->
  <div class="mb-6">
    <form wire:submit="save" class="space-y-4">
      <!-- エラーメッセージ -->
      @include('shared.form_errors')

      <!-- コメント入力 -->
      <div class="form-control">
        <div class="label">
          <span class="label-text">応援メッセージ</span>
        </div>
        <textarea wire:model="body"
            rows="4"
            class="textarea textarea-bordered w-full"
            placeholder="頑張っていますね！応援しています！"
            maxlength="{{ \App\Models\Comment::MAX_BODY_LENGTH }}"></textarea>
        <div class="label">
          <span class="label-text-alt">最大500文字</span>
        </div>
      </div>

      <!-- 送信ボタン -->
      <div class="form-control">
        <input type="submit" value="応援メッセージを送る" class="btn btn-success" wire:loading.attr="disabled">
      </div>
    </form>
  </div>

  <!-- 既存コメント一覧 -->
  <div class="space-y-4">
    <div>
      @forelse ($comments as $comment)
        <div wire:key="comment-{{ $comment->id }}">
          @include('comments._comment', ['comment' => $comment])
        </div>
      @empty
        <div class="hero min-h-32">
          <div class="hero-content text-center">
            <div>
              <p class="text-base-content/60 mb-2">
                まだ応援メッセージはありません
              </p>
              <p class="text-base-content/60 text-sm">
                最初の応援メッセージを投稿してみませんか？
              </p>
            </div>
          </div>
        </div>
      @endforelse
    </div>
  </div>
</div>
