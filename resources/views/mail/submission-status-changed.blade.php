<x-mail::message>
# Actualización de tu entrega

Hola **{{ $submission->assignmentMember->user->name }}**,

Tu archivo **{{ $submission->file_name }}** en la tarea **{{ $submission->assignment->name }}** del workspace **{{ $submission->assignment->workspace->name }}** tiene ahora el estado:

**@if ($submission->status === 'accepted')
Aceptada
@elseif ($submission->status === 'rejected')
Rechazada
@else
{{ $submission->status }}
@endif**

@if ($submission->status === 'rejected')
Puedes subir una nueva versión desde la aplicación si la tarea sigue abierta.
@endif

<x-mail::button :url="config('app.url')">
Abrir {{ config('app.name') }}
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
