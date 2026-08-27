<?php

namespace Tests\Unit;

use App\Support\GhostAction;
use App\Support\GhostActionContract;
use App\Support\GhostEdit;
use App\Support\GhostManifest;
use App\Support\GhostVerifier;
use Tests\TestCase;

/**
 * Contrat, claims, manifeste. Sans I/O métier.
 */
class GhostContractTest extends TestCase
{
    public function test_draft_exposes_contract_keys(): void
    {
        $a = GhostEdit::parse('Ajoute un champ salaire après contrat', GhostEdit::seed());
        $this->assertArrayHasKey('contract', $a);
        $this->assertArrayHasKey('stage', $a);
        foreach (['intent', 'actor', 'preconditions', 'authority', 'allowed_operations', 'forbidden_operations', 'expected_state', 'evidence_requirements', 'autonomy', 'confirmation_requirement', 'before_state', 'proposed_operations'] as $k) {
            $this->assertArrayHasKey($k, $a['contract'], $k);
        }
        $this->assertContains('salaire', $a['contract']['expected_state']['must_contain']);
        $this->assertTrue($a['contract']['confirmation_requirement']);
        $this->assertSame(GhostAction::AUTHORIZE, $a['stage']);
    }

    public function test_prepare_is_propose_technically(): void
    {
        $this->assertSame(GhostAction::PROPOSE, GhostAction::stageOf([
            'level' => GhostAction::PREPARE,
            'autonomy' => GhostAction::AUTO,
            'status' => 'preview',
        ]));
        $this->assertSame(GhostAction::AUTHORIZE, GhostAction::stageOf([
            'level' => GhostAction::PREPARE,
            'autonomy' => GhostAction::CONFIRM,
            'status' => 'preview',
        ]));
    }

    public function test_authorize_blocks_denied_ops(): void
    {
        $raw = [
            'id' => 'ga-x',
            'action' => 'order.refund',
            'level' => GhostAction::ACT,
            'autonomy' => GhostAction::DENY,
            'ops' => [['op' => 'order.refund']],
            'preview' => ['rembourse'],
            'before' => [],
            'status' => 'preview',
        ];
        $out = GhostActionContract::authorize($raw);
        $this->assertSame('blocked', $out['status']);
        $this->assertSame(GhostAction::DENY, $out['stage']);
        $this->assertSame([], $out['ops']);
    }

    public function test_apply_verifies_expected_state(): void
    {
        $seed = GhostEdit::seed();
        $a = GhostEdit::parse('Ajoute un champ salaire après contrat', $seed);
        $out = GhostEdit::apply($seed, $a);
        $this->assertSame('applied', $out['action']['status']);
        $this->assertSame(GhostAction::VERIFY, $out['action']['stage']);
        $this->assertSame(GhostVerifier::PASS, $out['action']['verification']['result']);
        $this->assertContains('salaire', array_column($out['blocks'], 'key'));
        $this->assertSame([], $out['action']['verification']['missing']);
    }

    public function test_claim_price_pass_fail_unknown(): void
    {
        $pass = GhostVerifier::verifyText('Cette offre coûte 180 €', [['prix' => 180, 'titre' => 'Cel']]);
        $this->assertSame(GhostVerifier::PASS, $pass['result']);
        $fail = GhostVerifier::verifyText('Cette offre coûte 999 €', [['prix' => 180, 'titre' => 'Cel']]);
        $this->assertSame(GhostVerifier::FAIL, $fail['result']);
        $unk = GhostVerifier::verifyText('Cette offre coûte 180 €', []);
        $this->assertSame(GhostVerifier::UNKNOWN, $unk['result']);
        $spaced = GhostVerifier::verifyText('Cette pièce coûte 2 400 €', [['prix' => '2 400 €', 'titre' => 'Cristal']]);
        $this->assertSame(GhostVerifier::PASS, $spaced['result']);
        $this->assertSame('2400', $spaced['claims'][0]['claim']['value']);
        $hay = GhostVerifier::evidenceFrom([['score' => 0.1800, 'lexical' => 0.42]]);
        $this->assertNotContains('1800', $hay['prices']);
        $this->assertSame([], $hay['prices']);
    }

    public function test_manifest_never_lists_deny_in_propose(): void
    {
        $g = GhostManifest::gates();
        foreach (GhostAction::DENIED as $op) {
            $this->assertNotContains($op, $g['prepare']);
            $this->assertNotContains($op, $g['propose']);
            $this->assertNotContains($op, $g['act']);
            $this->assertNotContains($op, $g['read']);
            $this->assertContains($op, $g['deny']);
            $this->assertFalse(GhostManifest::allows($op));
        }
        $this->assertContains('field.add', $g['prepare']);
        $this->assertSame($g['prepare'], $g['propose']);
    }

    public function test_forbidden_ops_live_on_the_contract(): void
    {
        $a = GhostAction::make('field.add', [[
            'op' => 'field.add', 'type' => 'digits', 'name' => 'Salaire', 'key' => 'salaire',
        ]], GhostEdit::seed(), 'Ajouter Salaire.');
        foreach (GhostAction::DENIED as $op) {
            $this->assertContains($op, $a['contract']['forbidden_operations']);
            $this->assertNotContains($op, $a['contract']['allowed_operations']);
        }
    }
}
