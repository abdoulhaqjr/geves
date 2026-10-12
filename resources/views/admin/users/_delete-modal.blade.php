{{-- Modale de suppression sécurisée, réutilisée par la liste et par la fiche.
     Le parent fournit l'état Alpine : delOpen (bool), delConfirm (string) et
     delUser ({ url, name }). Côté serveur : SoftDeletes (réversible), après
     saisie du mot « Supprimer » ET du mot de passe de l'administrateur. --}}
<x-ukc.slideover open="delOpen" onClose="delOpen = false; delConfirm = ''" title="Supprimer un compte" width="460px">
    <form method="POST" :action="delUser.url" autocomplete="off">
        @csrf
        @method('DELETE')

        <div style="display:flex;gap:10px;padding:12px 14px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:18px;">
            <span style="flex:0 0 auto;margin-top:1px;"><x-ukc.icon name="alert-triangle" :size="18" /></span>
            <div style="font-size:13.5px;line-height:1.55;">
                Le compte <strong x-text="delUser.name"></strong> sera supprimé du back-office et perdra
                <strong>immédiatement</strong> son accès. La suppression est <strong>douce</strong>
                (le compte reste récupérable en base), mais il n’existe pas d’écran de restauration.
            </div>
        </div>

        <div class="ukc-field" style="margin-bottom:14px;">
            <label>Tapez <code>Supprimer</code> pour confirmer</label>
            <input type="text" name="confirm" x-model="delConfirm" placeholder="Supprimer" autocomplete="off" required>
        </div>
        <div class="ukc-field">
            <label>Confirmez avec votre mot de passe</label>
            <input type="password" name="password" placeholder="Votre mot de passe administrateur" autocomplete="current-password" required>
        </div>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
            <button type="button" class="ukc-btn ukc-btn--ghost" @click="delOpen = false; delConfirm = ''">Annuler</button>
            <button type="submit" class="ukc-btn ukc-btn--danger" :disabled="delConfirm.trim() !== 'Supprimer'">
                <x-ukc.icon name="trash" :size="16" /> Supprimer définitivement
            </button>
        </div>
    </form>
</x-ukc.slideover>
