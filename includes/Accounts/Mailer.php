<?php
/**
 * Loyalty customer email messages.
 *
 * @package ITKayali\Loyalty\Accounts
 */

namespace ITKayali\Loyalty\Accounts;

use ITKayali\Loyalty\Core\AccountPage;

final class Mailer
{
    public function sendVerification(string $email, string $name, string $token, bool $password_setup = false): bool
    {
        $url = add_query_arg(
            array(
                'itk-loyalty-action' => 'verify-email',
                'token'              => $token,
            ),
            AccountPage::url()
        );

        if ($password_setup) {
            $subject = sprintf(__('E-Mail bestätigen & Passwort festlegen – %s', 'it-kayali-loyalty'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
            $message = sprintf(
                __("Hallo %s,\n\nbestätige bitte deine E-Mail-Adresse und lege anschließend dein persönliches Passwort für dein Treuekonto fest:\n%s\n\nDer Link ist 24 Stunden gültig. Danach kannst du dich mit E-Mail + Passwort sowohl über die Treuekonto-Seite als auch über die normale Mein-Konto-Seite anmelden.\n\nWenn du dich nicht registriert hast, kannst du diese E-Mail ignorieren.", 'it-kayali-loyalty'),
                $name,
                $url
            );
        } else {
            $subject = sprintf(__('E-Mail bestätigen – %s', 'it-kayali-loyalty'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
            $message = sprintf(
                __("Hallo %s,\n\nbitte bestätige deine E-Mail-Adresse für dein Treuekonto:\n%s\n\nDer Link ist 24 Stunden gültig.\n\nWenn du dich nicht registriert hast, kannst du diese E-Mail ignorieren.", 'it-kayali-loyalty'),
                $name,
                $url
            );
        }

        return wp_mail($email, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'));
    }

    public function sendMagicLink(string $email, string $name, string $token, string $password_token = ''): bool
    {
        $login_url = add_query_arg(
            array(
                'itk-loyalty-action' => 'magic-login',
                'token'              => $token,
            ),
            AccountPage::url()
        );

        $subject = sprintf(__('Dein Login-Link – %s', 'it-kayali-loyalty'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));

        if ('' !== $password_token) {
            $password_url = add_query_arg(
                array(
                    'itk-loyalty-action' => 'password-setup',
                    'token'              => $password_token,
                ),
                AccountPage::url()
            );

            $message = sprintf(
                __("Hallo %s,\n\nhier ist dein persönlicher Login-Link für dein Treuekonto:\n%s\n\nDer Login-Link ist 15 Minuten gültig und kann nur einmal verwendet werden.\n\nPasswort festlegen oder ändern:\n%s\n\nDer Passwort-Link ist 60 Minuten gültig und kann nur einmal verwendet werden. Mit deinem Passwort kannst du dich anschließend sowohl über die Treuekonto-Seite als auch über die normale Mein-Konto-Seite anmelden.\n\nWenn du den Login nicht angefordert hast, kannst du diese E-Mail ignorieren.", 'it-kayali-loyalty'),
                $name,
                $login_url,
                $password_url
            );
        } else {
            $message = sprintf(
                __("Hallo %s,\n\nhier ist dein persönlicher Login-Link für dein Treuekonto:\n%s\n\nDer Link ist 15 Minuten gültig und kann nur einmal verwendet werden.\n\nWenn du den Login nicht angefordert hast, kannst du diese E-Mail ignorieren.", 'it-kayali-loyalty'),
                $name,
                $login_url
            );
        }

        return wp_mail($email, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'));
    }

    public function sendEmailChangeVerification(string $email, string $name, string $token): bool
    {
        $url = add_query_arg(
            array(
                'itk-loyalty-action' => 'verify-email-change',
                'token'              => $token,
            ),
            AccountPage::url()
        );

        $subject = sprintf(__('Neue E-Mail-Adresse bestätigen – %s', 'it-kayali-loyalty'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $message = sprintf(
            __("Hallo %s,\n\nbitte bestätige deine neue E-Mail-Adresse:\n%s\n\nBis zur Bestätigung bleibt deine bisherige E-Mail-Adresse gültig. Der Link ist 24 Stunden gültig.\n\nWenn du diese Änderung nicht angefordert hast, ignoriere diese E-Mail.", 'it-kayali-loyalty'),
            $name,
            $url
        );

        return wp_mail($email, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'));
    }
}
