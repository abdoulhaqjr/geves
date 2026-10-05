<x-mail::message>
# Nouveau message de contact

**Événement :** {{ $event->title }}
**De :** {{ $senderName }} ({{ $senderEmail }})

---

{{ $body }}

---

Répondez directement à cet e-mail pour contacter l’expéditeur.
</x-mail::message>
