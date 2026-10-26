<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Submission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Complète les CO-AUTEURS des communications du colloque Eau au Togo.
 *
 * Commande : php artisan db:seed --class=ColloqueEauTogoCoauthorsSeeder
 * À lancer APRÈS ColloqueEauTogoSeeder (et ColloqueEauTogoComplementSeeder) :
 * il ne crée aucune communication, il complète celles déjà en base.
 *
 * ⚠️ Les co-auteurs vivaient en texte libre dans la colonne « Les autres auteurs
 * et affiliations » du formulaire (une par résumé), stockée telle quelle dans
 * `submission.meta['coauthors_raw']` par les deux seeders d'import mais jamais
 * transformée en auteurs. Ce texte est 100% libre (noms+affiliations mêlés,
 * listes numérotées, « Néant », parfois des mots-clés collés par erreur) : un
 * parseur automatique abîme des noms réels. La liste ci-dessous a donc été
 * extraite PUIS RELUE à la main (cf. la discipline des seeders d'import), et
 * c'est elle qui fait foi - visible dans le diff.
 *
 * ⚠️ Appariement par `sha256(meta.coauthors_raw)` : stable, autonome (pas besoin
 * du xlsx à l'exécution), et il couvre les deux imports (18/10 + 26/10) dont les
 * textes co-auteurs coïncident au octet près (couverture vérifiée : 40/40).
 *
 * ⚠️ On ne touche QUE le tableau `submission.authors` (affichage : livret,
 * programme, fiche). Aucune ligne `submission_authors`, aucun compte, aucune
 * notification : un co-auteur libre sans e-mail fiable n'est pas provisionné
 * comme compte (règle d'origine du colloque). L'auteur correspondant (authors[0])
 * n'est jamais modifié ; le déposant re-listé en co-auteur est dédoublonné par
 * ensemble de mots (accents/casse/ordre ignorés). Idempotent.
 */
class ColloqueEauTogoCoauthorsSeeder extends Seeder
{
    private const EVENT_SLUG = 'premier-colloque-eau-togo-2026';

    /**
     * Co-auteurs relus, par empreinte du texte brut de la cellule.
     *
     * @var string
     */
    private const CURATED_JSON = <<<'JSON'
[
    {
        "raw_sha256": "af2a15a76ec5a0cbb6e1bd629403260405dbfed5d04b2c379e8f7701f8994c87",
        "depositor": "Eric Bienvenu Armand FOTO",
        "coauthors": [
            {
                "name": "Bida Yandaï Olivera",
                "affiliation": ""
            },
            {
                "name": "Nicole Poumayé",
                "affiliation": ""
            },
            {
                "name": "Olga Biteman",
                "affiliation": ""
            },
            {
                "name": "Oscar Allahdin",
                "affiliation": ""
            },
            {
                "name": "Eric Foto",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "ccbbac60e0e4491f333f603d78681633640e592f6917e1cdaa15d033a5ac40c2",
        "depositor": "Safiata KIEMDE",
        "coauthors": [
            {
                "name": "Mariam Myriam ZONGO/ DAMA",
                "affiliation": ""
            },
            {
                "name": "Jérôme COMPAORE",
                "affiliation": ""
            },
            {
                "name": "François de Charles OUEDRAOGO",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "2be0ae232b07cd8d64fbee80a90b262c3d7731f85e1e664a6ae5320f7393cdf0",
        "depositor": "Abdoul Aziz TARPAGA",
        "coauthors": [
            {
                "name": "Isso Felix Bado",
                "affiliation": ""
            },
            {
                "name": "Adama Yameogo",
                "affiliation": ""
            },
            {
                "name": "Nicolas Kagambega",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "e5af09978274d7557db1a67bb762dbfb9051126ea363d7ac6db7cd9d34b9fa34",
        "depositor": "Kanle Grace-Natacha KUEVIAKOE",
        "coauthors": [
            {
                "name": "KUEVIAKOE Kanle Grace-Natacha",
                "affiliation": "Doctorant, Vishwakarma University, Pune, India"
            }
        ]
    },
    {
        "raw_sha256": "b96e5a18e2217f8f10b576ac6d2ffd875a4b50fd983e8a6cb74325e1d69870fb",
        "depositor": "Egbaou ASSOTE",
        "coauthors": [
            {
                "name": "ASSOGBA S. Claude-Gervais",
                "affiliation": "MC, faculté d'Agronomie de l'Université de Parakou"
            }
        ]
    },
    {
        "raw_sha256": "20d85993a97a3992ed523d7e377aadd95e64021772ae21d00a31265c327afb91",
        "depositor": "LAMINE DIOP",
        "coauthors": [
            {
                "name": "Ousseynou Badji",
                "affiliation": "Laboratoire LaBAAM, UGB"
            }
        ]
    },
    {
        "raw_sha256": "5d455a767f22a43e52da447575eb505133a396f27deaa078fe3ac87a0ab13e60",
        "depositor": "LAMINE DIOP",
        "coauthors": [
            {
                "name": "BADJI Ousseynou",
                "affiliation": "LaBAAM, Université Gaston Berger Senegal"
            }
        ]
    },
    {
        "raw_sha256": "35315f723bd4d39d6396b9c54bdd681d79f4b01a264b22f5885322bb72cb2d5f",
        "depositor": "Kossi Afelete ATIGAKU",
        "coauthors": [
            {
                "name": "Ibrahim TCHAKALA",
                "affiliation": ""
            },
            {
                "name": "Komlan Kwassi AGBOVI",
                "affiliation": ""
            },
            {
                "name": "Kokouvi Michel EBRI",
                "affiliation": ""
            },
            {
                "name": "Kouyole Clément NIKABOU",
                "affiliation": ""
            },
            {
                "name": "Jean pierre MAHE",
                "affiliation": ""
            },
            {
                "name": "Tchakouni SONDOU",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "20f17699ec94f82b6bb1e46a986015a3f85a23380e175192611dd1d027f690cf",
        "depositor": "Akpezounè Esso TCHAZIM",
        "coauthors": [
            {
                "name": "Assogba GUÉZÉRÉ",
                "affiliation": "Professeur titulaire au département de géographie, Laboratoire PREDES, Université de Kara"
            }
        ]
    },
    {
        "raw_sha256": "b85271e44ec9a5723cbc495c34011ab0fc5d47ac26940182949b4aeae73105d4",
        "depositor": "Kodjovi Gbégnon DAGOUDO",
        "coauthors": [
            {
                "name": "Bawa Limam Moctar",
                "affiliation": ""
            },
            {
                "name": "Kodom Tomkouani",
                "affiliation": ""
            },
            {
                "name": "Tchakala Ibrahim",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "c0842f912a10b2693212ed3ed8e9c55d1e021dc1f106f7299f67d44c2e135179",
        "depositor": "Dimiline DOROUWA",
        "coauthors": [
            {
                "name": "Komi Selom KLASSOU",
                "affiliation": "professeur titulaire, hydro-climatologue et gestions des ressources en eau, Université de Lomé (Togo)"
            }
        ]
    },
    {
        "raw_sha256": "2480af1b4deed2d474f6289fa7586cfe86b2ea4747fcbf0125d6b4ba0f065005",
        "depositor": "Yawavi Justine SANNI",
        "coauthors": [
            {
                "name": "HALATOKO Wemboo A",
                "affiliation": ""
            },
            {
                "name": "KPAIKPAI Pirenam",
                "affiliation": ""
            },
            {
                "name": "BADJABAISSI Essotolom",
                "affiliation": ""
            },
            {
                "name": "AWOUMENOU Koffi",
                "affiliation": ""
            },
            {
                "name": "DIKENI Kondi",
                "affiliation": ""
            },
            {
                "name": "TOUGLO Kossi",
                "affiliation": ""
            },
            {
                "name": "SOUROU Kadoukpe",
                "affiliation": ""
            },
            {
                "name": "NUTO Akoula Yaovi",
                "affiliation": ""
            },
            {
                "name": "KEDEYI Pana",
                "affiliation": ""
            },
            {
                "name": "TOMETY Akouvi Navaeme Yahse",
                "affiliation": ""
            },
            {
                "name": "ADIBOLO Kossi Devia Mawutowu",
                "affiliation": ""
            },
            {
                "name": "ATAKORA Magnou-léléng",
                "affiliation": ""
            },
            {
                "name": "ADADJISSO Komlan",
                "affiliation": ""
            },
            {
                "name": "TETTEKPOE Assionléo A.E. G",
                "affiliation": ""
            },
            {
                "name": "KADANG Kossi",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "d8596186530209c54d503ec95baa4243281d3a6b38bf2eae1fe90dab125b7278",
        "depositor": "Bouwèna N'DAH",
        "coauthors": [
            {
                "name": "Pyalo Atina BADAMELI",
                "affiliation": "Maitre de Conférences, PREDES, Géographie, Université de Kara, Kara, Togo"
            }
        ]
    },
    {
        "raw_sha256": "106853d77a7989ed58bd292db1155d7ed767cc9f5a458adbc59f2dda898db232",
        "depositor": "Kodjovi Senanou GBAFA",
        "coauthors": [
            {
                "name": "Kokou WOTODZO",
                "affiliation": "Laboratoire de Structure et de Mécanique des Matériaux : Université de Lomé Togo"
            },
            {
                "name": "K. Sena APEKE",
                "affiliation": "Laboratoire des sciences de l’ingénieur : Université de Lomé Togo"
            },
            {
                "name": "Fare BINGUITCHA",
                "affiliation": "Laboratoire de géosciences Eau et Environnement : Université de Lomé Togo"
            }
        ]
    },
    {
        "raw_sha256": "61475c42ef9ed6aefb8c7bdb0b0d09c432a1f4c977ec0e266291d08addaac2cb",
        "depositor": "MANEGAWENE JOSEPH OUEDRAOGO",
        "coauthors": [
            {
                "name": "Yacouba YIRA",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "221d36ab499b7725fd65bfa2218833db445cd480a3336d1eb0d4932ea2b1c057",
        "depositor": "Emilienne, Dossou Charles, Zakaria KABORE, LIGAN, NOUNTA",
        "coauthors": [
            {
                "name": "Charles Dossou LIGAN",
                "affiliation": "Université d’Abomey-Calavi"
            },
            {
                "name": "Zakaria NOUNTA",
                "affiliation": "Université Yambo OUOLOGUEM"
            }
        ]
    },
    {
        "raw_sha256": "8022ecdc9e3c7f165f974c9b5cb8df3f6d42f561ea35d8ccc59ef3b669049270",
        "depositor": "Dampalou DOUTI",
        "coauthors": [
            {
                "name": "GUEZERE Assogba",
                "affiliation": "Professeur de géographie, Laboratoire PREDES, Université de Kara, Togo"
            },
            {
                "name": "MASSELOT Cyril",
                "affiliation": "Professeur des SIC, Laboratoire CIMEOS, Université Bourgogne Europe, France"
            }
        ]
    },
    {
        "raw_sha256": "c7ed6b2e90505343d8e55c29475cf70c95d54698fd95593312d4281b9e8883d4",
        "depositor": "Kouassi Rodolphe Anoumou",
        "coauthors": [
            {
                "name": "AHOLOU Coffi Cyprien",
                "affiliation": "CERViDA - DOUNEDON, Université de Lomé"
            }
        ]
    },
    {
        "raw_sha256": "436c24b5594abb8f2fbd73c50c2794e659d184803292a175d993253ff6f80b6b",
        "depositor": "Koami DJOBOUEL",
        "coauthors": [
            {
                "name": "LARE Konnegbéne",
                "affiliation": "Laboratoire Pôle de Recherche et d’Expertise sur la Dynamique des Espaces et des Sociétés"
            },
            {
                "name": "KADOUZA Padabô",
                "affiliation": "Laboratoire Pôle de Recherche et d’Expertise sur la Dynamique des Espaces et des Sociétés"
            }
        ]
    },
    {
        "raw_sha256": "bedd4ea1adc2d68dc58cb278fba434f5a450cb78543311827593396db857d2f1",
        "depositor": "Estelle keziah SOGBO",
        "coauthors": [
            {
                "name": "KANATI Lardja",
                "affiliation": "Université de Kara (Togo), Laboratoire PREDES"
            }
        ]
    },
    {
        "raw_sha256": "ea202f1d4a1261e2bcea09f2980efc1e325dc2f379bbc71bcfa7a6c0c9fceffc",
        "depositor": "ABBA AMBA BRAHIM",
        "coauthors": [
            {
                "name": "Abba Amba BRAHIM",
                "affiliation": ""
            },
            {
                "name": "Simon AHOUANSOU MONTCHO",
                "affiliation": ""
            },
            {
                "name": "Pierre K. HOUNDONOUGBO",
                "affiliation": ""
            },
            {
                "name": "Romaric K. LALEYE",
                "affiliation": ""
            },
            {
                "name": "Antoine CHIKOU",
                "affiliation": ""
            },
            {
                "name": "Philippe A. LALEYE",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "b6d1321354f0bbb49063c5ec8ee345025e9ea7dfebc2ca9b18a6ba2f3f6bb1e1",
        "depositor": "ESSO-Essinam Samuel KPATCHA",
        "coauthors": [
            {
                "name": "Samuel Esso-Essinam KPATCHA",
                "affiliation": ""
            },
            {
                "name": "Seyf-Laye ALFA-SIKA MANDE",
                "affiliation": ""
            },
            {
                "name": "Nitale M'Balikine KROU",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "6406f020a8f67295172f50e2ed5b4e56f914e40feed6e4c7e37446d820afc4e8",
        "depositor": "ESSO-Essinam Samuel KPATCHA",
        "coauthors": [
            {
                "name": "Seyf-Laye ALFA-SIKA MANDE",
                "affiliation": ""
            },
            {
                "name": "Nitale M'Balikine KROU",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "a2c70758e55f2437c550f90c2810e229b073fe0afea19ef040711a7b4bc2851b",
        "depositor": "Djakaridja SORY",
        "coauthors": [
            {
                "name": "Raymond KABORE",
                "affiliation": ""
            },
            {
                "name": "Yacouba SANOU",
                "affiliation": ""
            },
            {
                "name": "Seyf-Laye ALPHA-SIKA MANDE",
                "affiliation": ""
            },
            {
                "name": "Samuel PARE",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "9b00859881c508bf5c7d06a59ecfc8e38404c1c974a10403400084af9725d63c",
        "depositor": "KALEGUE TAGUI HAIN",
        "coauthors": [
            {
                "name": "Kalegue Tagui Hain",
                "affiliation": ""
            },
            {
                "name": "Houénafa Aimé Chrysostome Gansa",
                "affiliation": ""
            },
            {
                "name": "Hyppolite Agadjihouèdé",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "b05f6812dcb9a859672082f563d0f3cac91f6f30638438aeaec0c9602a91b9cc",
        "depositor": "Kossitse Venyo AKPATAKU",
        "coauthors": [
            {
                "name": "Kossitse Venyo Akpataku",
                "affiliation": ""
            },
            {
                "name": "Akpénè Amenuvevega Dougna",
                "affiliation": ""
            },
            {
                "name": "Lallébila Tampo",
                "affiliation": ""
            },
            {
                "name": "Agbessi Koffi Sodomon",
                "affiliation": ""
            },
            {
                "name": "Mozimwè Ani",
                "affiliation": ""
            },
            {
                "name": "Seyf-Laye Alfa-Sika Mande",
                "affiliation": ""
            },
            {
                "name": "Bawa Limam Moctar",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "f034d57b451576b688bdda71888a9af4c9441b1390cdd5f5c96598db6a5614a7",
        "depositor": "Nougbognon Abraham TOSSOU",
        "coauthors": [
            {
                "name": "Abraham Nougbognon TOSSOU",
                "affiliation": ""
            },
            {
                "name": "Barnabé Koessi Lié ZOKPODO",
                "affiliation": ""
            },
            {
                "name": "Nodiet Melody DAHOU",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "634d7fe96e4a902109d60954574ddf6d77235d2d6e12138126d9f8cc78e8c98a",
        "depositor": "ESSOWEREOU ABEL ABLI",
        "coauthors": [
            {
                "name": "Tiatou Souho",
                "affiliation": "laboratoire des Sciences Agronomiques et Biologiques Appliquées, Université de Kara, Kara, Togo"
            },
            {
                "name": "Damipie Bomboma",
                "affiliation": "laboratoire des Sciences Agronomiques et Biologiques Appliquées, Université de Kara, Kara, Togo"
            },
            {
                "name": "Essoyodou Marcelle Kabassina",
                "affiliation": "laboratoire des Sciences Agronomiques et Biologiques Appliquées, Université de Kara, Kara, Togo"
            },
            {
                "name": "Sabiba Kou’santa Amouzou",
                "affiliation": "laboratoire des Sciences Agronomiques et Biologiques Appliquées, Université de Kara, Kara, Togo"
            }
        ]
    },
    {
        "raw_sha256": "1263f53ffc20a6c4efdf6cb0d231866ddf796d22140ba1647cf14eaba0b52319",
        "depositor": "Malè BATCHASSIDO",
        "coauthors": [
            {
                "name": "GBEKLEY Efui Holaly",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "23002899180c7a6ab28c07219f10412fe991e712315d1de4d2cb0c2adb966a5e",
        "depositor": "Maliawaï HASSIM",
        "coauthors": [
            {
                "name": "GUEZERE Assogba",
                "affiliation": "Pôle de Recherche et d’Expertise sur la Dynamique des Espaces et des Sociétés (PREDES)"
            },
            {
                "name": "NANOINI Damitonou",
                "affiliation": "Pôle de Recherche et d’Expertise sur la Dynamique des Espaces et des Sociétés (PREDES)"
            }
        ]
    },
    {
        "raw_sha256": "2154bb7dae5439fe74c77725c62112723bca9c7e6212bdbee50bb6ce751a4062",
        "depositor": "Emilienne KABORE",
        "coauthors": [
            {
                "name": "LIGAN Charles Dossou",
                "affiliation": "Université d’Abomey-Calavi"
            },
            {
                "name": "NOUNTA Zakaria",
                "affiliation": "Université Yambo OUOLOGUEM"
            }
        ]
    },
    {
        "raw_sha256": "1c80b1c6c199818ca31a2c4539550a887cab5ff44af226412924980cdb1a7d13",
        "depositor": "Cyr Gervais ETENE",
        "coauthors": [
            {
                "name": "ETENE Cyr Gervais",
                "affiliation": ""
            },
            {
                "name": "CHABI A B. Philippe",
                "affiliation": ""
            },
            {
                "name": "ALAGBE S. Benjamin",
                "affiliation": ""
            },
            {
                "name": "AGBOMANHENAN Saturne",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "5e25f7001b74c0e279d4d37608a140879aef90a7a77eacfdbaf20ac29e1d34ad",
        "depositor": "Mozimwè ANI",
        "coauthors": [
            {
                "name": "Fabrice Amen Messan LAWSON",
                "affiliation": ""
            },
            {
                "name": "Mozimwè ANI",
                "affiliation": ""
            },
            {
                "name": "Boris AGBOGAN",
                "affiliation": ""
            },
            {
                "name": "Bruno Moussa KAFANDO",
                "affiliation": ""
            },
            {
                "name": "Basile HECTOR",
                "affiliation": ""
            },
            {
                "name": "Jean-Michel VOUILLAMOZ",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "7836b5cca536dc846f48fdb83543dbb1c727905b297123546a60cf4c71d25893",
        "depositor": "Mozimwè ANI",
        "coauthors": [
            {
                "name": "Bruno Moussa KAFANDO",
                "affiliation": ""
            },
            {
                "name": "Fabrice Amen Messan LAWSON",
                "affiliation": ""
            },
            {
                "name": "Mozimwè ANI",
                "affiliation": ""
            },
            {
                "name": "Basile HECTOR",
                "affiliation": ""
            },
            {
                "name": "Jean-Michel VOUILLAMOZ",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "1560683b8ea7a2253d72b826f37796e04c6d4faed28960aed17668f7f69e7440",
        "depositor": "Essodina Fabrice SIZING",
        "coauthors": [
            {
                "name": "Essodina Fabrice SIZING",
                "affiliation": ""
            },
            {
                "name": "Agbessi Koffi SODOMON",
                "affiliation": ""
            },
            {
                "name": "Kossitse Venyo AKPATAKU",
                "affiliation": ""
            },
            {
                "name": "Seyf-Laye ALFA-SIKA MANDE",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "214a78a0fe957f7df055d50536db2373281c8208a3a0fe6797d293328de05946",
        "depositor": "Oswald Augias Eros KAMBIRET KAMBIRET",
        "coauthors": [
            {
                "name": "KAMBIRET Oswald Augias Eros",
                "affiliation": "Laboratoire d'Études et de Recherches sur les Milieux et les Territoires (LERMIT), Université Joseph KI-ZERBO, Burkina Faso"
            },
            {
                "name": "SODORE Abdoul Azise",
                "affiliation": "Laboratoire d'Études et de Recherches sur les Milieux et les Territoires (LERMIT), Université Joseph KI-ZERBO, Burkina Faso"
            }
        ]
    },
    {
        "raw_sha256": "50510e41a920e8b1520048d3320c611b3af3796592ae6705c69c1ee1132151dd",
        "depositor": "Ayida SESSO",
        "coauthors": [
            {
                "name": "OURO-AGORO Fouozou",
                "affiliation": ""
            },
            {
                "name": "AKPATAKU Kossitse Venyo",
                "affiliation": ""
            },
            {
                "name": "ALFA-SIKA MANDE Seyf-Laye",
                "affiliation": ""
            },
            {
                "name": "TCHAKALA Ibrahim",
                "affiliation": ""
            },
            {
                "name": "BAWA L. Moctar",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "f66eb19caa59c715071425398f74b7a34241241c476dd1d881362e1e74be2ef3",
        "depositor": "Djimayi AROUNA",
        "coauthors": [
            {
                "name": "Djimayi AROUNA",
                "affiliation": ""
            },
            {
                "name": "Essodina Fabrice SIZING",
                "affiliation": ""
            },
            {
                "name": "Agbessi Koffi SODOMON",
                "affiliation": ""
            },
            {
                "name": "Kossitse Venyo AKPATAKU",
                "affiliation": ""
            },
            {
                "name": "Seyf-Laye ALFA-SIKA MANDE",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "fada2335ba63fbb9f9d03c57ce5bc6f031ed0e61b9cf6520163745111a16948a",
        "depositor": "Yadoutè BINANGMA",
        "coauthors": [
            {
                "name": "Kossitse Venyo Akpataku",
                "affiliation": ""
            },
            {
                "name": "Lallébila Tampo",
                "affiliation": ""
            },
            {
                "name": "Seyf-Laye Alfa-Sika Mande",
                "affiliation": ""
            }
        ]
    },
    {
        "raw_sha256": "17c02ab8afb0a3f48873702b84b62e1956d658cd65855290e3a3837f2d7227c9",
        "depositor": "Nakpane LABANTE",
        "coauthors": [
            {
                "name": "Komlan KOUZAN",
                "affiliation": "HisDAf, Département d'Histoire, Université de Kara"
            },
            {
                "name": "Bammoy NABE",
                "affiliation": "HisDAf, Département d'Histoire, Université de Kara"
            }
        ]
    }
]
JSON;

    public function run(): void
    {
        $event = Event::query()->where('slug', self::EVENT_SLUG)->first();
        if ($event === null) {
            throw new RuntimeException(
                'Événement '.self::EVENT_SLUG.' introuvable : lancez d’abord '
                .'ColloqueEauTogoSeeder (puis ColloqueEauTogoComplementSeeder).'
            );
        }

        /** @var list<array{raw_sha256: string, depositor: string, coauthors: list<array{name: string, affiliation: string}>}> $curated */
        $curated = json_decode(self::CURATED_JSON, true, 512, JSON_THROW_ON_ERROR);
        $parHash = [];
        foreach ($curated as $cellule) {
            $parHash[$cellule['raw_sha256']] = $cellule['coauthors'];
        }

        $apparies = 0;
        $misesAJour = 0;
        $coauteurs = 0;

        foreach (Submission::query()->where('event_id', $event->id)->get() as $submission) {
            $raw = $submission->meta['coauthors_raw'] ?? null;
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }
            $liste = $parHash[hash('sha256', $raw)] ?? null;
            if ($liste === null) {
                // Cellule « Néant »/mots-clés (aucun co-auteur réel), ou texte
                // modifié après la relecture : on ne devine rien, on passe.
                continue;
            }
            $apparies++;

            $auteurs = is_array($submission->authors) ? array_values($submission->authors) : [];
            $correspondant = $auteurs[0] ?? null;

            // On RECONSTRUIT : correspondant (intact) + co-auteurs relus, chacun
            // écarté s'il redit le correspondant ou un co-auteur déjà listé.
            $resultat = [];
            $vus = [];
            if (is_array($correspondant)) {
                $resultat[] = $correspondant;
                $vus[] = $this->cle((string) ($correspondant['name'] ?? ''));
            }
            foreach ($liste as $ca) {
                $cle = $this->cle($ca['name']);
                if ($cle === '' || in_array($cle, $vus, true)) {
                    continue;
                }
                $vus[] = $cle;
                $resultat[] = [
                    'name' => $ca['name'],
                    'email' => null,
                    'affiliation' => $ca['affiliation'] !== '' ? $ca['affiliation'] : null,
                    'corresponding' => false,
                ];
                $coauteurs++;
            }

            if ($submission->authors !== $resultat) {
                $submission->authors = $resultat;
                $submission->save();
                $misesAJour++;
            }
        }

        $this->command?->info(
            "Co-auteurs Eau-Togo : {$apparies} communications appariées, "
            ."{$misesAJour} mises à jour, {$coauteurs} co-auteurs écrits."
        );
    }

    /**
     * Clé de comparaison tolérante (accents, casse et ordre des mots ignorés),
     * pour ne jamais lister le déposant deux fois ni répéter un co-auteur.
     */
    private function cle(string $name): string
    {
        $base = Str::lower(Str::ascii($name));
        $base = preg_replace('/[^a-z0-9 ]+/', ' ', $base) ?? '';
        $mots = array_values(array_filter(explode(' ', $base), static fn (string $m): bool => $m !== ''));
        sort($mots);

        return implode(' ', $mots);
    }
}
