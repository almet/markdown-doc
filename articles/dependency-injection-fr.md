Réaliser un injecteur de dépendances, en utilisant de bonnes pratiques logicielles
==================================================================================

Un injecteur de dépendances ? Peut être cela ne vous parle il pas vraiment. Tant mieux, l'objectif de cet article est d'éclaircir ces termes, et de présenter une implémentation que j'ai eu l'occasion de mettre en place, en essayant de m'appuyer sur de bonnes pratiques logicielles.

Cet article est basé sur mon expérience personnelle, ainsi que sur des recherches effectuées lors de la réalisation d'un composant logiciel. L'objectif n'est aucunement d'imposer ma vision des choses, mais bel et bien de partager les interrogations, réflexion et découvertes que nous avons eu alors.

Nous parlerons ici de l'injecteur de dépendances de Spiral, un framework maison réalisé avec quelques amis, et dont l'objectif principal est de découvrir les rouages des frameworks, ainsi que de nous initier aux bonnes pratiques logicielles.

Alors que nous travaillions sur ce projet, notre principal but était de réellement comprendre, dans le détail, comment un injecteur de dépendances pouvait fonctionner. _Ré-inventer la roue_, pour mieux comprendre comment une roue fonctionne, en quelque sorte.

Aussi, l'objectif de ce document n'est pas de fournir une documentation sur l'utilisation du composant, mais bien d'expliquer *comment* nous l'avons réalisé.

L'injecteur de dépendances est disponible dans une version intégrée à Spiral ou dans une version _standalone_. Vous pouvez trouver le code sur le [dépôt mercurial](http://bitbucket.org/ametaireau/spiral/) du projet.

A l'heure ou j'écris ces lignes, l'injecteur de dépendances de spiral n'est pas encore terminé (sept 09), mais est dans un état avancé, et devrait être disponible en novembre 2009.

L'ensemble des exemples de ce document sont en PHP, mais les concepts discutés ici peuvent être (et sont!) implémentés dans d'autres langages.

Donc, parlons un peu d'injection de dépendances !

Comment gérons-nous nos objets ?
----------------------------------

Avant toute chose, il est indispensable de bien comprendre ce qu'est l'inversion de contrôle.

![une glace ?](articles/dependency-injection-fr/icecream.png)

Lorsque nous réalisons des logiciels en utilisant le _paradigme_ orienté objet, nous travaillons avec des classes, et la majeure partie du temps, nous faisons interagir ces classes entre elles. En pratique, certaines classes sont dépendantes d'autres classes.

Pour mettre un exemple derrière ces concepts, tout au long de ce document, nous allons nous mettre dans la peau d'_Alice_, une jeune fille qui adore manger des glaces, spécialement celles à la fraise !

Certains disent même qu'Alice est dépendante de la glace à la fraise.
   
    class Alice {
       
        public function mangerGlace(){
            $glace = new GlaceALaFraise();
            $glace->manger();
        }
    }
   
Il est clair, au regard de cette implémentation, qu'à chaque fois qu'Alice mange une glace, il s'agit d'une glace à la fraise. Génial,  mais un jour, la mère d'Alice souhaite lui faire découvrir d'autres parfums...

En réalité, avec cette implémentation, il est impossible de changer la glace qu'Alice va manger.

Inversion de Contrôle (IoC)
--------------------------

### Principe

![Don't call me, I'll call you!](articles/dependency-injection-fr/holywood-principle.png)

Il apparait donc nécessaire de supprimer les dépendances entre nos deux classes, pour permettre à Alice de gouter de nouveaux parfums.

Comment ? C'est assez simple, regardez donc le code:

    class Alice {
       
        public function mangerGlace(Glace $glace){
            $glace->manger();
        }
    }   

Quand Alice mange une glace (via la méthode `mangerGlace`), nous devons lui passer la glace, ce n'est plus elle qui choisit, *nous* le faisons à sa place.

Ce principe est connu comme étant **le principe d'Hollywood**: _"Ne nous appelez pas, nous vous appellerons"_. En d'autres termes, n'utilisez pas l'opérateur `new` dans vos classes, mais préférez passer (ou qu'on vous passe) les objets par référence.

Alice peut faire d'autres choses avec sa glace, la laisser tomber par terre par exemple (oups!), grâce à la méthode `lacherGlace()`. Nous pouvons alors choisir de passer la glace à cette méthode également, ou choisir de la donner directement à Alice, la laissant s'occuper du reste, et évitant de lui passer une glace pour chaque action qui en nécessite une.

    class Alice {
        protected $_glace = null;
       
        public function setGlace(Glace $glace){
            $this->_glace = $glace;
        }

        public function mangerGlace(){
            $this->_glace->manger();
        }

        public function lacherGlace(){
            $this->_glace->lâcher();
        }
    }   

Il est bien plus facile maintenant de choisir la glace à donner a Alice, et ainsi de contrôler les dépendances d'Alice vis à vis de la glace.

Ici, il subsiste des dépendances dans le code. Il s’agit de dépendances vis à vis de contrats (interfaces) et non d’implémentations données (classes), puisque j’ai choisi d’utiliser le paradigme de [programmation par contrats](http://fr.wikipedia.org/wiki/Programmation_par_contrat).

Et c'est tout pour le principe d'inversion de contrôle (pas les glaces à la fraise, contrôler les dépendances)! Il s'agit _simplement_ du fait d'inverser le flux de contrôle de vos application, en déléguant à un plus haut niveau la création des objets.

### Injection de dépendances

Maintenant que le concept d'inversion de contrôle est clair, expliquons ce qu'est l'injection de dépendances. Les deux concepts sont assez proches, et souvent utilisés de pair, mais il est important de bien saisir la frontière entre les deux.

Dans la méthode `mangerGlace`, nous considérons que la glace en question est déjà donnée à Alice. C'est un comportement vraiment utile: Nous n'avons plus à nous occuper de la manière dont la glace est arrivée là, nous l'avons déjà (dans une propriété privée par exemple).
 
Dans la section précédente, Alice était _dépendante_ de sa glace. En inversant le flux de contrôle, le comportement d'Alice vis à vis des glaces est plus facilement contrôlable, et testable (utiliser des _mocks_, ou _bouchons de tests_ est aussi facile que de régler une propriété, nous parlerons de tests plus tard).

Notre travail (celui de la mère d'Alice), est de créer les objets et de les passer à Alice. Les _injecter_ est le bon mot. En utilisant des mutateurs, ou en utilisant le constructeur, injectant les objets nécessaires.

Allons-y:
   
    $alice = new Alice();
    $glaceAuPaté = new GlaceAuPaté();
    $alice->setGlace($glaceAuPaté);
    
L'inversion de contrôle est donc le fait d'exposer des méthodes publiques (ou des constructeurs) pour régler certaines propriétés, et l'injection de dépendances et le fait de, justement, injecter ces dépendances, utiliser ces méthodes et constructeurs.

### Un Conteneur ?

L'exemple utilisé jusqu'ici est volontairement simple, et il à été choisi afin d'expliquer les concepts le plus clairement possible: Nous avons uniquement deux classes, et une dépendance.

En pratique, vous serez surement d'accord pour dire qu'un projet est rarement aussi simple. Aussi, dans les projets importants, la gestion du cycle de vie des objets et de l'injection de leurs propriété peut rapidement devenir un vrai casse tête.

L'idéal est alors d'automatiser le processus de création, d'injection et de gestion de ces cycles de vie. Le conteneur fait exactement ça.

Pourquoi "conteneur" ? Parce que la création automatique et l'injection est effectuée grâce à un objet, qui se charge de contenir toutes les informations sur les dépendances. Une fois les objets crées, le conteneur garde une référence vers ces derniers au cas ou nous en aurions encore besoin (voir la  définition de portée d'un service - les "scopes"- plus loin)

Le conteneur va donc se charger d'injecter les objets pour nous, en quelque sorte, il fait le travail de la Mère d'Alice à sa place.

Nous souhaitons donc que lorsque nous appellerons Alice, via le conteneur, elle nous soit retourné avec une glace déjà injectée, prête à être mangée !

    $alice = $container->getService('Alice');
    $alice->mangerGlace();

Ici, le conteneur à injecté la bonne glace à Alice (peu importe laquelle d'ailleurs, nous souhaitons juste avoir une glace)

Si la glace elle même avait été dépendante d'autres objets (disons, des noix de coco par exemple), c'est le rôle du conteneur que de résoudre l'ensemble des dépendances, dans le bon ordre, simplifiant au maximum la tâche de gestion des dépendances entre les objets et les classes, laissant la tâche simple pour le développeur.

Concepts logiciels
-----------------

Maintenant que les concepts d'inversion de contrôle et d'injection de dépendances sont clairs (enfin, j'espère!), nous pouvons commencer à parler de _comment_ nous avons réalisé cette bibliothèque.

Les concepts discutés ici sont des concepts assez simples, dont le principal objectif est de fournir une structure solide aux composants. Chaque composant à ainsi un rôle et un emplacement précis au sein de notre architecture.

### Le Schéma
![Le Schéma, avec les services, méthodes, et arguments](articles/dependency-injection-fr/schema.png)

Dans le schéma, et dans l'injecteur de dépendances en général, un "service" est un objet qui est géré par le conteneur.

Le schéma représente les liens entre les différents services. Il décrit les dépendances de nos objets.

Si vous connaissez le patron de conception de _fabrique abstraite_, vous pouvez vous représenter le schéma comme une configuration alors que le conteneur serait la fabrique elle même (ou quelque chose d'approchant).

Le schéma contient toutes les informations sur les méthodes qui doivent êtres appelées pour injecter les objets, le type des arguments qui doivent être passés, et tout autre type d'information potentiellement utile au moment de l'injection.

Pour en revenir à notre exemple, le schéma contiendrait des informations sur le type de glace qui doit être passée à Alice (Une glace à la fraise bien sur!), et sur la manière de donner cette glace à Alice (via la méthode `setGlace()`)

Jusqu'à maintenant, nous avons parlé de dépendances simples, mais le schéma peut aussi gérer d'autres types de services, méthodes et arguments. Tout est décrit dans les sections suivantes: `Services`, `Methods` et `Attibutes`.

#### Services

Un service représente un objet. Dans notre exemple, la Glace et Alice sont des services.

Un service se compose d':

* un nom
* un ensemble de méthodes
* une manière de se construire
* une portée (scope)

La portée d'un service défini comment la durée de vie des services doit être gérée par le conteneur: Est-ce que le service doit rester dans le conteneur pendant toute la durée du script (singleton), ou doit il être systématiquement supprimé après avoir été construit (prototype)? 

L'instance de l'objet courant peut être la même pour l'ensemble des services si la portée du service est définie comme étant un _singleton_, ou être à chaque fois différente si la portée est définie comme _prototype_.

D'autres types de portées peuvent êtres imaginées comme une portée de "session", qui retournerait la même instance durant une session unique, ou une sorte de portée "immortelle", qui retournerait toujours le même objet, en faisant persister cet objet à travers différentes sessions.

L'injecteur de dépendances est fourni avec les types de service suivants:

**Défaut**
:	Un service "simple", composé de méthodes, et qui peut être construit comment un simple objet.

**Alias**
:	Un alias vers un autre service. Seul le nom est différent. Ce type de service permet de gérer facilement les dépendances dans le temps. "Pour le moment, il s'agit d'un alias, mais peut être qu'un jour nous aurons besoin d'un autre type de service".
   
**Héritage de services**
:	Plutôt que de se répéter maintes et maintes fois lors de la description de services qui se ressemblent, il est possible d'utiliser l'héritage. Cela ressemble grandement à l'héritage de classes: les méthodes que vous redéfinissez ou ajoutez dans les services enfants écraseront ceux des parents.

#### Méthodes

Chaque service contient des méthodes.

Une méthode permet d'injecter certains paramètres dans nos services, ou de définir certaines ressources qui doivent être appelées au moment de la construction des services. Dans le cas d'Alice, `setGlace()` est une méthode.

Une méthode est composée d':

* un nom,
* optionellement, un nom de classe
* une liste d'arguments
* une information disant si la méthode est statique ou non

Voici les différents types de méthodes actuellement implémentées:

**Défaut**
:	Une simple méthode, avec des arguments. Peut être une méthode statique

**Attributs**
:	Utilisé pour régler directement les propriétés en utilisant les attributs publics de l'objet ($service->attribut = $valeur`).    Ce type de méthode peut contenir uniquement un argument. Il peut paraître étrange de gérer les attributs comme des méthodes. En réalité, il est important de comprendre la différence entre une méthode et un argument. Alors qu'un argument représente une valeur, une méthode représente une manière d'utiliser ces arguments. Dès lors, il parait plus logique de gérer les attributs comme des méthodes que comme des arguments.

**Rappels (callbacks)**
:	Avant ou après la création de vos services, il est possible d'appeler des méthodes spécifiques, appelées méthodes de rappel.

#### Arguments

Les méthodes contiennent donc des arguments, et il existe plusieurs types d'arguments également.
Les arguments sont le bout de la chaine services / méthodes / arguments.

**Défaut**
:	Types PHP natifs (int, string, float etc)

**Conteneur**
:	Il est possible d'injecter directement le conteneur. Ce type d'argument n'est utilisé que par les services qui nécessitent d'utiliser le conteneur. Ils sont appelées services "ContainerAware".

**Service courant**
:	Il est possible d'injecter le service en cours, et de l'utiliser comme argument. En pratique, ceci est uniquement utile pour les méthodes de rappel (callback)

**Argument vide**
:    Il s'agit d'un type d'argument qui na pas de valeur. L'argument "conteneur" et "service courant" étendent ce type. Attention, l'argument vide est différent de null.

**Référence à un service**
:	C'est un des types d'argument le plus utilisé, il représente un autre service.

**Argument résolu grâce aux services**
:	Parfois, il est utile d'utiliser un service pour récupérer un argument, je pense à la configuration entres autres. Ce type d'argument utilise donc une méthode spécifique d'un autre service pour être résolu.


### Stratégies de construction

![Stratégies de construction](articles/dependency-injection-fr/construction.png)

Maintenant que nous avons un schéma qui représente les relations entre nos services, nous allons nous occuper de la construction de ces services.

Nous avons choisi de séparer complètement les logiques de construction et de définition, pour permettre de favoriser un maximum d'usages possibles pour l'un et l'autre des composants.

Chaque type, dans le schéma, peut être lié à un type de stratégie pour se construire. Il y à donc plusieurs stratégies de construction pour les services, les méthodes et les arguments.

L'intérêt d'utiliser des stratégies de construction est de permettre à chacun de nos types, dans le schéma, de se construire _eux même_, en utilisant leur méthode `build()`, qui va elle déléguer la tache de construction aux stratégies.

En interne, il est possible d'utiliser des stratégies de construction différentes, et d'en changer à tout moment. Ce comportement suit, en fait, [le patron de conception stratégie](http://fr.wikipedia.org/wiki/Strat%C3%A9gie_(patron_de_conception)).

### _Builders_ / Monteurs

Puisque nous parlons de patrons de conception (design patterns), parlons du motif "Monteur".

Vous serez sans doute d'accord avec moi pour dire qu'écrire un schéma entièrement à la main, en utilisant les classes dont nous avons parlé un peu plus haut peut s'avérer rapidement assez pénible. En tout cas, pour l'avoir expérimenté lors de l'écriture des tests, je ne peut pas dire qu'il s'agisse d'un gain de temps.

Une solution pratique consiste à utiliser le motif _Monteur_. L'idée est d'écrire le schéma sous une forme sympathique et facile à écrire pour nous, développeurs, et d'utiliser une classe intermédiaire pour transformer notre représentation du schéma dans la représentation compréhensible par notre composant.

Cette classe intermédiaire _monte_ donc notre schéma, en déchiffrant une autre structure.

![Builders](articles/dependency-injection-fr/builders.png)

Le premier type de _monteur_ qui me vient à l'esprit (le plus pratique, en fait), est le _monteur_ XML. Il est capable de lire un schéma, décrit au format XML, et de construire le schéma en utilisant les objets de notre bibliothèque. L'écriture du schéma XML à plusieurs avantages: il est facile à écrire, permet d'utiliser des outils extérieurs pour l'éditer facilement, et bénéficie, grâce a [XML Schema](http://fr.wikipedia.org/wiki/XML_Schema), d'une auto-complétion et d'une vérification à la volée, lors de l'écriture.

Les injecteurs de dépendances ([Google Juice](http://code.google.com/p/google-guice/) et [Spring](www.springsource.org) permettent l'utilisation des annotations directement dans le code, pour définir les règles d'injection (le schéma pour nous).

Malgré qu'il ne s'agisse pas d'un comportement recommandé (les annotations ne sont exploitables que par un type d'injecteur, même si [une spécification est actuellement en cours](http://jcp.org/en/jsr/summary?id=dependency+injection)), il est possible d'utiliser la réflexion sur un projet, et de la combiner a l'utilisation d'annotations pour déduire facilement la structure de notre schéma, pour le remplir ensuite à notre guise.

Ce composant est également un _monteur_.

Les monteurs suivants sont fournis de base:

* Le monteur XML
* Le monteur PHP, qui utilise une interface fluide, pour permettre des configurations de ce type: `$monteur->addService()->withMethod()`
* Le monteur Réflexion (utilise la réflexion sur nos classes pour construire un schéma)

### _Dumpers_

Un _dumper_ est un objet qui copie des données d'un type de format vers un autre. Effectivement, il peut s'avérer utile d'avoir une manière simple de se représenter un schéma déjà défini.

![Dumpers](articles/dependency-injection-fr/dumpers.png)

Les dumpers permettent par exemple de représenter un schéma sous une forme graphique, ou bien sous une forme plus compréhensible pour nous, avec un simple texte par exemple.

Il est donc vraiment facile de montrer les dépendances de vos projets, en utilisant simplement le dumper Dot (qui est le format utilisé par [graphviz](www.graphviz.com)) par exemple.

Voici la liste des dumpers :

* Le dumper texte
* Le dumper Dot (graphviz)
* Le dumper XML
* Le dumper PHP

Ces deux composants laissent entrevoir des pistes intéressantes: il est possible d'écrire ses classes, puis de générer un schéma partiel grâce au monteur "réflexion", de le _dumper_ en XML, de le compléter à la main (avec de l'auto-complétion), et de le monter à nouveau, grâce au monteur XML.

Implémentation
---------------

Voici quelques règles que nous avons suivi lors du développement en lui même:

### Espaces de noms / PHP 5.3
Alors que nous nous penchions sur ce projet, PHP 5.3 n'était pas encore sorti, mais puisque cette version apportait des fonctionnalités vraiment intéressantes (late static binding, espaces de noms et closures), nous avons choisi d'utiliser alors la version en cours de développement de PHP 5.3.

Maintenant, PHP 5.3 est disponible en version stable, et permet de faire fonctionner notre projet.

Notre bibliothèque se sépare selon les espaces de noms suivants:

* L'espace de nom `Construction`, qui contiens toutes les classes liées au concept de construction (les stratégies de construction)
* L'espace de nom `Definition` , qui contiens le schéma.
* L'espace de nom `Transformation` qui contiens les Dumpers et les _Monteurs_

### Développement piloté par les tests (TDD)
Ce projet fut également l'occasion d'écrire nos premiers tests, pour finir par utiliser une approche pilotée par les tests.

Le développement piloté par les tests préconise de réaliser ses tests **avant** d'écrire ses classes. Au début, ça chatouille un peu, mais on
comprends rapidement l'intérêt de cette méthodologie, qui est une vraie bonne pratique.

Écrire ses tests avant d'avoir codé la classe nous oblige à la fois à privilégier une utilisation logique de nos composants, et à fixer les interfaces. Le code produit est réellement comme on souhaite l'utiliser, et non pas comme il est plus facile de l'implémenter.

Écrire des tests, c'est aussi penser à l'ensemble des scénarios d'utilisation de ces classes, même les plus farfelus. Cela nous oblige à réfléchir à tous ces cas d'utilisation, et ça fait le plus grand bien !
 
Pour revenir aux tests, ils permettent de tester que notre application se comporte bien comme elle le devrait, mais cela permet aussi de détecter rapidement des régressions que de nouvelles fonctionnalités peuvent apporter.

Rapidement, on écrit des tests pour tout: bugs, idées, etc. Ça favorise vraiment le développement d'une application.

Un peu plus haut, je parlais de Mock objets (ou objets bouchon, en français). Je vous laisse consulter [l'article wikipédia sur les mocks]((http://en.wikipedia.org/wiki/Mock_object) pour vous faire une idée plus précise, mais il s'agit, rapidement, d'objets qui permettent de simuler le comportement d'autres objets, ces derniers pouvant communiquer avec le suite de tests.

### Interfaces

Dans l'ensemble de nos classes, nous essayons de travailler avec les interfaces plutôt qu'avec des implémentations particulières. 
Pourquoi ? Parce que travailler avec des interfaces nous permet de changer à tout moment d'implémentation !

Dans le cas d'Alice, elle n'est pas dépendante d'un type particulier de glace (celle à la fraise), mais simplement aux glaces, à l'_interface_ `Glace`, pour être exact.

Chacune des interfaces ci dessous représente un comportement décrit plus haut:

* Schema
* Service
* Method
* Argument
* Container
* Dumper
* Builder

### L'écriture des classes
Pour écrire nos classes, et parce que nous souhaitons fournir un système facilement extensible, nous fournissons quasi systématiquement une interface, et une classe abstraite, pour que chaque concept puisse être étendu facilement.
 
D'ailleurs, l'écriture des classes en elle même est assez simple, une fois que tous les concepts ont étés décrit et sont clairs.

Vous pouvez regarder le code sur [le dépôt mercurial de spiral](http://bitbucket.org/ametaireau/spiral/src/)

Je ne vois pas grand chose à ajouter à propos de l'implémentation, si ce n'est peut être, qu'il est indispensable de commenter votre code: cela permet aux potentiels futur contributeurs de s'y retrouver facilement, et de comprendre comment tout cela fonctionne !

Conclusion
-----------

J'espère que cet article vous aura intéressé, en tout cas j'ai pris beaucoup de plaisir à l'écrire, et vous aurez au moins appris comment nous avons choisi d'implémenter un injecteur de dépendances en utilisant quelques bonnes pratiques logicielles !

Si vous êtes intéressés pour discuter à ce propos, ou à propos d'autres concepts logiciels, vous pouvez me contacter sur alexis [chez] supinfo [point] com, je serais ravi d'échanger avec vous.
