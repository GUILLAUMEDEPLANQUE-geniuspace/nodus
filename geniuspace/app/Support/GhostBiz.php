<?php

namespace App\Support;

/**
 * Business agent. READ / PREPARE / ACT. Le ledger est déterministe (démo).
 * Pas d’email réel : apply enregistre le résultat.
 */
class GhostBiz
{
    public static function ledger(): array
    {
        $boughtX = 143;
        $alsoY = 22;
        $yesterday = 847;
        $shipped = 721;

        return [
            'productX' => 'cel-14',
            'productY' => 'print-cel',
            'boughtX' => $boughtX,
            'alsoY' => $alsoY,
            'optInXnotY' => $boughtX - $alsoY,
            'yesterday' => $yesterday,
            'shipped' => $shipped,
            'excluded' => $yesterday - $shipped,
            'inactive' => 1842,
            'recommended' => 'B',
            'campaigns' => [
                'A' => ['n' => 3421, 'why' => 'file trop large, faible intention'],
                'B' => ['n' => 1842, 'why' => 'inactifs 90 j, même relique'],
            ],
        ];
    }

    public static function route(string $message): ?string
    {
        $m = mb_strtolower($message);
        if (preg_match('/suivi/u', $m)) {
            return 'tracking';
        }
        if ((preg_match('/inactif|trois derniers mois/u', $m) && preg_match('/campagne|relance/u', $m))
            || preg_match('/clients inactifs/u', $m)) {
            return 'relance';
        }
        if (preg_match('/campagne/u', $m) && preg_match('/client|achet|command/u', $m)) {
            return 'campaign';
        }
        if (preg_match('/quels clients|ont achet/u', $m)) {
            return 'orders';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function plan(string $message): array
    {
        $kind = self::route($message);
        if ($kind === 'tracking') {
            return self::prepareTracking();
        }
        if ($kind === 'relance') {
            return self::proposeRelance();
        }
        if ($kind === 'campaign' || preg_match('/lance b\b|campagne b/u', mb_strtolower($message))) {
            return self::prepareCampaign($message)['action'];
        }

        return self::readOrders($message);
    }

    /**
     * @return array<string, mixed>
     */
    public static function readOrders(string $message): array
    {
        $L = self::ledger();
        $m = mb_strtolower($message);
        if (preg_match('/hier|suivi/u', $m)) {
            return [
                'id' => GhostAction::id('biz'),
                'action' => 'orders.filter',
                'level' => GhostAction::OBSERVE,
                'autonomy' => GhostAction::AUTO,
                'ops' => [],
                'preview' => [
                    $L['yesterday'].' commandes hier.',
                    $L['shipped'].' éligibles (expédiées, suivi, opt-in).',
                    $L['excluded'].' exclues — pas encore expédiées.',
                ],
                'before' => [],
                'status' => 'preview',
                'result' => (string) $L['shipped'],
            ];
        }

        return [
            'id' => GhostAction::id('biz'),
            'action' => 'customers.segment',
            'level' => GhostAction::OBSERVE,
            'autonomy' => GhostAction::AUTO,
            'ops' => [],
            'preview' => [
                $L['boughtX'].' clients ont commandé le cel.',
                $L['alsoY'].' ont déjà le print.',
                $L['optInXnotY'].' éligibles à une offre print (opt-in, pas Y).',
            ],
            'before' => [],
            'status' => 'preview',
            'result' => (string) $L['optInXnotY'],
        ];
    }

    /**
     * @return array{action: array<string, mixed>, campaign: array<string, mixed>}
     */
    public static function prepareCampaign(string $message): array
    {
        $L = self::ledger();
        $m = mb_strtolower($message);
        $discount = preg_match('/15\s*%/u', $m) ? 15 : (preg_match('/10\s*%/u', $m) ? 10 : (preg_match('/5\s*%/u', $m) ? 5 : 15));
        $inactive = (bool) preg_match('/inactif/u', $m);
        $volume = $inactive ? $L['campaigns']['B']['n'] : $L['optInXnotY'];
        $excluded = $inactive ? 0 : $L['alsoY'];
        $campaign = [
            'id' => GhostAction::id('cmp'),
            'audience' => $inactive ? 'inactifs 90 j · même relique' : 'achat cel · pas print · opt-in',
            'offer' => $inactive ? 'Relance print' : 'Print du cel',
            'discount' => $discount,
            'channel' => 'email',
            'volume' => $volume,
            'excluded' => $excluded,
            'status' => 'ready',
            'why' => $inactive ? $L['campaigns']['B']['why'] : 'Cohorte d’acheteurs du cel, sans le print.',
        ];
        $auto = GhostAction::autonomyFor('campaign.launch', ['volume' => $volume, 'discount' => $discount]);
        $confirmLine = $auto === GhostAction::CONFIRM
            ? ($volume >= 100 ? 'Volume ≥ 100 · confirmation exigée.' : 'Remise > 10 % · confirmation exigée.')
            : 'Volume < 100 et remise ≤ 10 % · envoi possible sans confirmation.';
        $action = [
            'id' => GhostAction::id('biz'),
            'action' => 'campaign.create',
            'level' => GhostAction::PREPARE,
            'autonomy' => $auto,
            'ops' => [[
                'op' => 'campaign.create',
                'audience' => $campaign['audience'],
                'offer' => $campaign['offer'],
                'discount' => $discount,
                'channel' => 'email',
            ]],
            'preview' => [
                'Audience · '.$volume.' clients.',
                $excluded ? $excluded.' exclus (déjà Y).' : 'Aucune exclusion.',
                'Offre · '.$campaign['offer'].' −'.$discount.' %.',
                'Canal · email. Envoi non lancé.',
                $confirmLine,
            ],
            'before' => [],
            'status' => 'preview',
            'citations' => [['label' => $campaign['offer'], 'id' => $campaign['id']]],
        ];

        return compact('action', 'campaign');
    }

    /**
     * @param  array<string, mixed>  $campaign
     * @return array<string, mixed>
     */
    public static function launch(array $campaign): array
    {
        if (($campaign['status'] ?? '') === 'launched') {
            return GhostAction::blocked('Déjà envoyée.');
        }
        $auto = GhostAction::autonomyFor('campaign.launch', [
            'volume' => (int) ($campaign['volume'] ?? 0),
            'discount' => (int) ($campaign['discount'] ?? 0),
        ]);

        return [
            'id' => GhostAction::id('biz'),
            'action' => 'campaign.launch',
            'level' => GhostAction::ACT,
            'autonomy' => $auto,
            'ops' => [['op' => 'campaign.launch', 'campaign_id' => $campaign['id'] ?? '']],
            'preview' => ['Envoi · '.($campaign['volume'] ?? 0).' messages.'],
            'before' => [],
            'status' => $auto === GhostAction::DENY ? 'blocked' : 'preview',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function proposeRelance(): array
    {
        $L = self::ledger();

        return [
            'id' => GhostAction::id('biz'),
            'action' => 'campaign.create',
            'level' => GhostAction::PREPARE,
            'autonomy' => GhostAction::AUTO,
            'ops' => [],
            'preview' => [
                'Campagne A · '.$L['campaigns']['A']['n'].' clients — '.$L['campaigns']['A']['why'].'.',
                'Campagne B · '.$L['campaigns']['B']['n'].' clients — '.$L['campaigns']['B']['why'].'.',
                'Je recommande B.',
            ],
            'before' => [],
            'status' => 'preview',
            'ask' => 'Laquelle ?',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function prepareTracking(): array
    {
        $L = self::ledger();
        $auto = GhostAction::autonomyFor('message.send', ['volume' => $L['shipped']]);

        return [
            'id' => GhostAction::id('biz'),
            'action' => 'message.send',
            'level' => GhostAction::ACT,
            'autonomy' => $auto,
            'ops' => [['op' => 'message.send', 'kind' => 'tracking', 'volume' => $L['shipped']]],
            'preview' => [
                $L['shipped'].' clients éligibles.',
                $L['excluded'].' commandes exclues (pas expédiées).',
                $auto === GhostAction::CONFIRM ? 'Volume ≥ 100 · confirmation exigée.' : 'Prêt à envoyer.',
            ],
            'before' => [],
            'status' => 'preview',
        ];
    }
}
