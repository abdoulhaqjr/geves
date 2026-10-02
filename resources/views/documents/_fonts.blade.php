{{-- Polices des documents (badges, attestations).

     ⚠️ À inclure PARTOUT où une mise en page du concepteur est rendue. Le
     concepteur chargeait Jost et Playfair Display, mais ni l'aperçu, ni
     l'attestation délivrée, ni le PDF ne les chargeaient : le document dessiné
     en Jost ressortait en police système, avec d'autres métriques - du texte qui
     ne tenait plus dans sa boîte, et une maquette méconnaissable.

     Les graisses reprennent exactement celles du concepteur
     (`admin/documents/template-designer.blade.php`) : une graisse absente est
     synthétisée par le navigateur, ce qui change encore le rendu. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
