<x-mail::message>
# Nouveau message de contact

**Édition :** {{ $edition->title }}
**De :** {{ $senderName }} ({{ $senderEmail }})

---

{{ $body }}

---

Répondez directement à cet e-mail pour contacter l’expéditeur.
</x-mail::message>
