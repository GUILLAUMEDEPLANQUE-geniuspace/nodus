<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForumVideoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('nodes')->insertOrIgnore([
            ['id' => 'zoro', 'slug' => 'zoro', 'kind' => 'character', 'title' => 'Roronoa Zoro', 'subtitle' => 'Sabreur', 'summary' => 'Trois sabres. Enfant du Node.', 'body' => '', 'hero' => '/realms/zoro.jpg', 'skin' => 'living', 'featured' => 0],
            ['id' => 'sanji', 'slug' => 'sanji', 'kind' => 'character', 'title' => 'Sanji', 'subtitle' => 'Cuisinier', 'summary' => '', 'body' => '', 'hero' => '/realms/sanji.jpg', 'skin' => 'living', 'featured' => 0],
            ['id' => 'chopper', 'slug' => 'chopper', 'kind' => 'character', 'title' => 'Tony Tony Chopper', 'subtitle' => 'Médecin', 'summary' => '', 'body' => '', 'hero' => '/realms/chopper.jpg', 'skin' => 'living', 'featured' => 0],
        ]);
        DB::table('edges')->insertOrIgnore([
            ['from_id' => 'onepiece', 'to_id' => 'zoro', 'kind' => 'parent_of', 'label' => 'Sabreur'],
            ['from_id' => 'onepiece', 'to_id' => 'sanji', 'kind' => 'parent_of', 'label' => 'Cuisinier'],
            ['from_id' => 'onepiece', 'to_id' => 'chopper', 'kind' => 'parent_of', 'label' => 'Médecin'],
        ]);

        DB::table('threads')->insertOrIgnore([
            ['id' => 'th-op-3', 'node_id' => 'onepiece', 'kind' => 'forum', 'title' => 'Le One Piece existe-t-il vraiment ?', 'author' => 'Usopp', 'body' => 'Accroche indexée. Le Legacy garde les meilleures théories.', 'cover' => '/realms/sea-hero.jpg', 'views' => 4200, 'fires' => 88, 'replies_count' => 3],
            ['id' => 'th-op-j1', 'node_id' => 'onepiece', 'kind' => 'blog', 'title' => 'Journal de bord — Loguetown', 'author' => 'Nami', 'body' => 'Chronique de l’équipage. Maillage vers le wiki et la boutique.', 'cover' => '/realms/nami.jpg', 'views' => 890, 'fires' => 12, 'replies_count' => 0],
            ['id' => 'th-op-j2', 'node_id' => 'onepiece', 'kind' => 'blog', 'title' => 'Les reliques du Sunny', 'author' => 'Franky', 'body' => 'Chaque fichier Drive devient une note de journal.', 'cover' => '/realms/sea-hero.jpg', 'views' => 310, 'fires' => 4, 'replies_count' => 0],
            ['id' => 'th-or-1', 'node_id' => 'orion', 'kind' => 'forum', 'title' => 'Culture fit : corriger en public ?', 'author' => 'Recruteur', 'body' => 'Débat interne. Le Legacy devient la bible ATS.', 'cover' => '/realms/studio-hero.jpg', 'views' => 210, 'fires' => 9, 'replies_count' => 2],
        ]);
        DB::table('threads')->where('id', 'th-op-1')->update(['views' => 12800, 'fires' => 340, 'replies_count' => 4]);
        DB::table('threads')->where('id', 'th-op-2')->update(['views' => 2100, 'fires' => 41, 'replies_count' => 2]);

        DB::table('replies')->insert([
            ['thread_id' => 'th-op-1', 'author' => 'Robin', 'body' => 'Ace a choisi sa mort. Le texte source (ch. 573) le dit sans détour. C’est la réponse Legacy.', 'votes' => 42],
            ['thread_id' => 'th-op-1', 'author' => 'Zoro', 'body' => 'Whitebeard n’a pas perdu. Il a tenu le monde. Point.', 'votes' => 28],
            ['thread_id' => 'th-op-1', 'author' => 'Nami', 'body' => 'La carte de Marineford dans le Drive montre les positions. Joignez la relique.', 'votes' => 19],
            ['thread_id' => 'th-op-2', 'author' => 'Luffy', 'body' => 'La carte est dans le Drive. On la vend aussi en print limité.', 'votes' => 11],
            ['thread_id' => 'th-or-1', 'author' => 'Maya', 'body' => 'Message privé + source Drive. On ne lynche pas un junior en public.', 'votes' => 8],
            ['thread_id' => 'th-or-1', 'author' => 'Kenji', 'body' => 'Sauf si le lore faux est déjà indexé. Alors on corrige, sources, sans ego.', 'votes' => 6],
        ]);
        DB::table('live_messages')->insert([
            ['thread_id' => 'th-op-1', 'author' => 'Sanji', 'body' => 'Ace 💔'],
            ['thread_id' => 'th-op-1', 'author' => 'Chopper', 'body' => 'Je relis le chapitre là'],
            ['thread_id' => 'th-op-1', 'author' => 'Ussop', 'body' => 'gif : marineford-feu.gif'],
            ['thread_id' => 'th-op-2', 'author' => 'Nami', 'body' => 'Print restock vendredi'],
            ['thread_id' => 'th-or-1', 'author' => 'Maya', 'body' => 'Salon ouvre à 14h'],
        ]);
        DB::table('drive_files')->insert([
            ['node_id' => 'onepiece', 'title' => 'Carte Grand Line annotée.pdf', 'path' => '/realms/sea-hero.jpg', 'kind' => 'pdf', 'locked' => 0],
            ['node_id' => 'onepiece', 'title' => 'OST · Binks no Sake.mp3', 'path' => '/media/teaser.mp4', 'kind' => 'audio', 'locked' => 0],
            ['node_id' => 'onepiece', 'title' => 'Masterclass Haki (premium).mp4', 'path' => '/media/teaser.mp4', 'kind' => 'video', 'locked' => 1],
            ['node_id' => 'orion', 'title' => 'Bible recruteur.pdf', 'path' => '/realms/studio-hero.jpg', 'kind' => 'pdf', 'locked' => 0],
            ['node_id' => 'orion', 'title' => 'Épreuve culture-fit.mp4', 'path' => '/media/orion.mp4', 'kind' => 'video', 'locked' => 1],
            ['node_id' => 'atelier', 'title' => 'Making-of L\'Aube.mp4', 'path' => '/media/atelier.mp4', 'kind' => 'video', 'locked' => 1],
            ['node_id' => 'atelier', 'title' => 'Certificat RWA.pdf', 'path' => '/realms/actor-hero.jpg', 'kind' => 'pdf', 'locked' => 0],
        ]);
        DB::table('guild_messages')->insert([
            ['node_id' => 'onepiece', 'author' => 'Luffy', 'body' => 'On part à 16h. Reliques dans le Drive.'],
            ['node_id' => 'onepiece', 'author' => 'Zoro', 'body' => 'J’ai posté sur Marineford.'],
            ['node_id' => 'onepiece', 'author' => 'Nami', 'body' => 'Print restock — boutique.'],
            ['node_id' => 'orion', 'author' => 'Maya', 'body' => 'Trois candidats en étape 2.'],
        ]);
        DB::table('wiki_pages')->insert([
            ['node_id' => 'onepiece', 'title' => 'Haki', 'body' => 'Trois formes. Maillage vers la masterclass vidéo et le forum Legacy.'],
            ['node_id' => 'onepiece', 'title' => 'Marineford', 'body' => 'Guide. Sujet forum lié : qui avait raison ?'],
            ['node_id' => 'orion', 'title' => 'Les 7 étapes', 'body' => 'Culture fit → Drive → cas → live → offre. Pas de CV PDF orphelin.'],
        ]);
        DB::table('media')->where('node_id', 'onepiece')->update(['views' => 5400, 'rating' => '4.7']);
        DB::table('media')->where('node_id', 'atelier')->update(['views' => 890, 'rating' => '4.9']);
        DB::table('media')->where('node_id', 'orion')->update(['views' => 210, 'rating' => '4.5']);
    }
}
