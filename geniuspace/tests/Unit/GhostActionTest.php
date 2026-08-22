<?php

namespace Tests\Unit;

use App\Llm\CckCatalog;
use App\Support\GhostAction;
use App\Support\GhostBiz;
use App\Support\GhostEdit;
use Tests\TestCase;

class GhostActionTest extends TestCase
{
    public function test_adds_salary_after_contract(): void
    {
        $a = GhostEdit::parse('Ajoute un champ salaire après contrat', GhostEdit::seed());
        $this->assertSame('field.add', $a['ops'][0]['op']);
        $this->assertSame('Salaire', $a['ops'][0]['name']);
        $this->assertSame('digits', $a['ops'][0]['type']);
        $this->assertSame('contrat', $a['ops'][0]['after']);
        $blocks = GhostEdit::apply(GhostEdit::seed(), $a)['blocks'];
        $keys = array_column($blocks, 'key');
        $this->assertSame(array_search('salaire', $keys, true), array_search('contrat', $keys, true) + 1);
    }

    public function test_does_not_duplicate_salaire(): void
    {
        $once = GhostEdit::apply(GhostEdit::seed(), GhostEdit::parse('Ajoute un champ salaire après contrat', GhostEdit::seed()));
        $twice = GhostEdit::parse('Ajoute un champ salaire après contrat', $once['blocks']);
        $this->assertSame([], $twice['ops']);
        $this->assertStringContainsString('existe déjà', $twice['preview'][0]);
    }

    public function test_ici_without_cursor_asks_where(): void
    {
        $a = GhostEdit::parse("Mets une photo de l'atelier ici", GhostEdit::seed(), []);
        $this->assertSame('Où ?', $a['ask'] ?? null);
        $this->assertSame([], $a['ops']);
    }

    public function test_atelier_image_after_cursor(): void
    {
        $a = GhostEdit::parse("Mets une photo de l'atelier ici", GhostEdit::seed(), [
            'cursor' => ['block' => 'b2', 'position' => 'after'],
        ]);
        $this->assertSame('media.insert', $a['ops'][0]['op']);
        $this->assertSame('m-atelier-nuit', $a['ops'][0]['media_id']);
        $this->assertSame('preview', $a['status']);
    }

    public function test_asks_which_image_when_generic(): void
    {
        $a = GhostEdit::parse('Mets une photo ici', GhostEdit::seed(), [
            'cursor' => ['block' => 'b1', 'position' => 'after'],
        ]);
        $this->assertStringContainsString('Laquelle', $a['ask'] ?? '');
        $this->assertSame([], $a['ops']);
    }

    public function test_playlist_under_presentation(): void
    {
        $a = GhostEdit::parse('Ajoute la playlist Ambient 01 sous la présentation', GhostEdit::seed());
        $this->assertSame('playlist.insert', $a['ops'][0]['op']);
        $this->assertSame('pl-ambient', $a['ops'][0]['playlist_id']);
        $this->assertSame('presentation', $a['ops'][0]['after']);
    }

    public function test_industry_fiche_is_preview_only(): void
    {
        $seed = GhostEdit::seed();
        $a = GhostEdit::parse('Fais-moi une fiche pour cette offre industrielle', $seed);
        $this->assertGreaterThanOrEqual(3, count($a['ops']));
        $this->assertSame('preview', $a['status']);
        $this->assertSame(array_column($seed, 'id'), $a['before']);
        $this->assertCount(5, $seed);
    }

    public function test_undo_restores_snapshot(): void
    {
        $seed = GhostEdit::seed();
        $a = GhostEdit::parse('Ajoute un champ salaire après contrat', $seed);
        $out = GhostEdit::apply($seed, $a);
        $this->assertGreaterThan(count($seed), count($out['blocks']));
        $back = GhostEdit::undo($out['blocks'], $out['action'], $seed);
        $this->assertCount(count($seed), $back);
    }

    public function test_denies_refund(): void
    {
        $a = GhostEdit::parse('Rembourse cette commande', GhostEdit::seed());
        $this->assertSame('blocked', $a['status']);
        $this->assertFalse(GhostAction::may('order.refund'));
    }

    public function test_counts_buyers_without_sending(): void
    {
        $a = GhostBiz::readOrders('Quels clients ont acheté le cel ?');
        $this->assertSame('observe', $a['level']);
        $this->assertStringContainsString('143', implode(' ', $a['preview']));
    }

    public function test_campaign_15_percent_requires_confirm(): void
    {
        $out = GhostBiz::prepareCampaign('Lance une campagne pour les clients qui ont acheté le cel et n\'ont pas le print. Propose-leur le print avec 15 % de réduction.');
        $this->assertSame('prepare', $out['action']['level']);
        $this->assertSame(15, $out['campaign']['discount']);
        $this->assertSame('confirm', $out['action']['autonomy']);
        $this->assertSame('ready', $out['campaign']['status']);
    }

    public function test_tracking_volume_requires_confirm(): void
    {
        $a = GhostBiz::prepareTracking();
        $this->assertSame('message.send', $a['action']);
        $this->assertSame('confirm', $a['autonomy']);
        $this->assertStringContainsString('721', implode(' ', $a['preview']));
        $this->assertStringContainsString('126', implode(' ', $a['preview']));
    }

    public function test_small_send_can_be_auto(): void
    {
        $this->assertSame('auto', GhostAction::autonomyFor('message.send', ['volume' => 40, 'discount' => 0]));
        $this->assertSame('auto', GhostAction::autonomyFor('campaign.launch', ['volume' => 40, 'discount' => 5]));
    }

    public function test_launch_stays_preview(): void
    {
        $c = GhostBiz::prepareCampaign('campagne print 15%')['campaign'];
        $l = GhostBiz::launch($c);
        $this->assertSame('preview', $l['status']);
        $this->assertSame('act', $l['level']);
    }

    public function test_catalog_exposes_capabilities(): void
    {
        $caps = CckCatalog::capabilities();
        $this->assertTrue($caps['digits']['can_insert']);
        $this->assertSame('field', $caps['digits']['placement']);
        $this->assertSame('block', $caps['image']['placement']);
        $this->assertSame(['media'], $caps['image']['requires']);
        $this->assertSame('Prix / nombre', $caps['digits']['label']);
    }
}
