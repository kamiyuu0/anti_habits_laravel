<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

abstract class Controller
{
    /** Turbo Stream での応答を受け付けるリクエストか */
    protected function wantsTurboStream(Request $request): bool
    {
        return str_contains((string) $request->header('Accept'), 'text/vnd.turbo-stream.html');
    }

    /** Turbo Stream 形式のレスポンスを返す */
    protected function turboStream(string $view, array $data = [], int $status = 200): Response
    {
        return response()
            ->view($view, $data, $status)
            ->header('Content-Type', 'text/vnd.turbo-stream.html; charset=utf-8');
    }

    /** Turbo Stream なら指定ビューで、そうでなければ元のページへ 303 で戻す */
    protected function respondToggle(Request $request, string $turboStreamView, array $data = []): Response|RedirectResponse
    {
        if (! $this->wantsTurboStream($request)) {
            return back(303);
        }

        return $this->turboStream($turboStreamView, $data);
    }
}
