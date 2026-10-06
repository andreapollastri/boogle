@php
$exportHttp = is_array($exception->http) ? $exception->http : null;
if ($exportHttp !== null) {
    if (! config('boogle.ui.show_http_headers', true)) {
        unset($exportHttp['headers']);
    }
    if (! config('boogle.ui.show_http_session', true)) {
        unset($exportHttp['session']);
    }
}
$exportData = [
    'issue_code' => $exception->issue_code,
    'project' => $project->title,
    'exception' => $exception->exception,
    'error' => $exception->error,
    'status' => $exception->status,
    'created_at' => $exception->created_at->format('d M Y H:i'),
    'host' => $exception->host,
    'file' => $exception->file,
    'line' => $exception->line,
    'class' => $exception->class !== $exception->exception ? $exception->class : null,
    'method' => $exception->method,
    'full_url' => $exception->full_url,
    'http' => $exportHttp,
    'executor' => $exception->executor,
    'additional' => $exception->additional,
    'user' => $exception->user,
    'storage' => $exception->storage,
];
@endphp
@php
// Public share pages never expose the affected end user.
if ($public ?? false) {
    unset($exportData['user']);
}
@endphp
<script type="application/json" id="exception-data">@json($exportData)</script>
