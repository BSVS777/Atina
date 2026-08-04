import { Passkeys } from '@laravel/passkeys';

declare global {
    interface Window {
        Passkeys: typeof Passkeys;
    }
}

window.Passkeys = Passkeys;
window.dispatchEvent(new CustomEvent('passkeys:ready'));
