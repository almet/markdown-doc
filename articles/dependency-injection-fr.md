Cet article est basé sur mon experience personelle, ainsi que sur des recherches effectuées lors de 
la réalisation d'un composant logiciel. J'ai alors réellement découvert des choses interessantes, et 
souhaite les partager.

Nous parlerons ici de l'injecteur de dépendences de Spiral, un framework maison dont je suis à l'origine, 
avec quelques amis, et dont l'objectif principal est de découvrir les rouages des frameworks, ainsi que de nous initier à l'architecture logicielle.

Cet injecteur de dépendances est également disponible dans une version __standalone__. Vous pouvez trouver le code sur son
 [dépôt mercurial](http://bitbucket.org/ametaireau/spiral/).

A l'heure ou j'écris ces lignes, l'injecteur de dépendances de spiral n'est pas encore terminé (sept 09), mais est dans un état avancé,
et devrait etre disponible en novembre 2009.

Introduction
-------------

Nous allons discuter d'injection de dépendances, et plus spécialement des reflections que nous avons eu alors que nous travaillions sur cette bibliothèque.

Cet article s'adresse à la fois aux personnes qui ne sont pas famillières avec le concept d'injection de dépendances, et aux personnes qui le comprennent déjà.

Nous allons essayer de parler de bonnes pratiques logicielles, et d'architecture logicielle, d'une manière plus générale. Alors que nous travaillions sur ce projet, notre
principal but était de réellement comprendre comment un injecteur de dépendances pouvait fonctionner. Ré-inventer la roue, pour mieux comprendre comment une roue fonctionne.

Aussi, l'objectif de ce document n'est pas de fournir une documentation exaustive sur l'utilisation du composant, mais d'expliquer *comment* nous l'avons réalisé. 

L'ensemble des exemples de ce document sont en PHP, mais les concepts discutés ici peuvent (et sont!) implémentés dans d'autres langages

Donc, parlons un peu d'injection de dépendances !

Comment gérons-nous nos objets
----------------------------------

Avant toute chose, il est indispensable d'expliquer ce qu'est l'inversion de contrôle. Commençons par notre pain quotidien: la manière dont nous gérons nos objets. 

![une glace ?](articles/dependency-injection/icecream.png)

Lorsque nous réalisons des logiciels, en utilisant le paradigme orienté objet, nous travaillons avc des classes, et, la majeure partie du temps, nous faisons intéragir ces classes entre elles.
En pratique, certaines classes sont dépendantes d'autres classes.

Pour mettre un exemple derrière ces concepts, tout au long de ce document, nous allons imaginer que nous sommes Alice, une jeune fille qui aime manger des glaces, qui adore manger des glaces, et spécialement celles à la fraise ! 

Disons alors qu'Alice est dépendante de la glace à la fraise.
	
	class Alice {
		
		public function mangerGlace(){
			$glace = new GlaceALaFraise();
			$glace->manger();
		}
	}
	
Il est clair, au regard de cette implementation, qu'à chaque fois qu'alice mange une glace, il s'agit d'une glace à la fraise. Génial, mais, un jour, la mère d'Alice souhaite qu'Alice découvre d'autres parfums.

En réalité, avec cette implementation, il est impossible de changer la glace qu'Alice va manger.

Inversion of Control (IoC)
--------------------------

### Principe

![Don't call me, I'll call you!](articles/dependency-injection/holywood-principle.png)

Donc, il apparait necessaire de suprimmer les dépendances entre nos deux classes, pour permettre à Alice de gouter de nouveaux parfums. Comment ? 

C'est assez simple, regardez donc le code:

	class Alice {
		
		public function mangerGlace(Glace $glace){
			$glace->manger();
		}
	}	

Quand alice mange une glace (via la methode `mangerGlace`), nous devons lui passer la glace, ce n'est plus elle qui choisit, *nous* le faisons à sa place.

Ce principe est connu comme étant **le principe d'Hollywood**: "Ne vous apellez pas vous meme, on vous apellera", ou, en d'autres termes, n'utilisez pas l'opérateur `new` dans vos classes, 
préférez plutôt passer vos objets par reference.

Alice peut faire d'autres choses avec sa glace, la laisser tomber par terre par exemple (oups!), grace à la methode `lacherGlace`.
Nous pouvons alors choisir de passer la glace à cette methode également, ou choisir de la donner directement à Alice, la laissant s'occuper du reste.

	class Alice {
		protected $_glace = null;
		
		public function setGlace(Glace $glace){
			$this->_glace = $glace;
		}

		public function mangerGlace(){
			$this->_glace->manger();
		}

		public function lacherGlace(){
			$this->_glace->lacher();
		}
	}	

Ici, nous pouvons choisir quel parfum est bon pour Alice, et il est assez facile de controller les dépendances d'Alice.

C'est tout pour le principe d'inversionde contrôle ! Il s'agit simplement du fair d'inverser le flux de contrôle de vos application, en délégant à un plus haut niveau la création des objets.

### Injection de dépendances

Maintenant que le concept d'inversion de contrôle est clair, expliquons ce qu'est l'injection de dépendances.

Dans la méthode `mangerGlace`, nous considérons que la glace en question est déjà donnée à Alice. C'est un comportement 
vraiment utile: Nous n'avons pas à nous occuper de la manière dont la glace est arrivée là, nous l'avons déjà (dans une propriété privée par exemple)
 
Dans la section précédente, Alice était _dépendente_ de sa glace. En inversant le flux de controle, le comportement d'Alice vis à vis des glaces est plus facilement
testable (utiliser des _mocks_, ou _bouchons_ est plus facile, nous parlerons de tests plus tard).

Notre travail (celui de la mère d'Alice), est de créer les objets et de les passer à Alice. Les _injecter_ est le bon mot. En utilisant des mutateurs, ou en utilisant le constructeur, injectant les objets necessaires.

Allons-y:
	
	$alice = new Alice();
	$glaceAuPaté = new GlaceAuPaté();
	$alice->setGlace($glaceALaBanane);

### Un Conteneur ?

L'exemple que j'ai choisi est volontairement simple, et je l'ai choisi afin d'expliquer les concepts clairement. 
Nous avons uniquement deux classes, et une dépendance.


En pratique, il est assez rare qu'un projet soit aussi simple. Aussi, dans les projets importants, la gestion du cycle de vie des objets peut rapidement devenir un vrai casse tete.

L'idéal est alors d'automatiser le processus de création et de gestion de ces cycles de vie. C'est le rôle du conteneur.

Pourquoi "conteneur" ? Parce que la création automatique et l'injection est effectuée grace à un objet, qui se charge de contenir toutes les informations sur les dépendances.
Une fois les objets crées, le conteneur garde une référence vers ces derniers au cas ou nous en aurions encore besoin.

Toujours avec le meme exemple, le conteneur va se charger d'injecter les objets pour nous, tout seul. Il s'occupe de faire la travail de la Mère d'Alice.

Le comportement final que nous souhaitons, est que lorsque nous appelerons Alice, via le conteneur, elle nous soit retourné avec une glace déjà injectée, prete à etre utilisée. 

	$alice = $container->getService('Alice');
	$alice->mangerGlace();

Ici, le conteneur à injecté la bonne glace à Alice (peu importe laquelle, nous souhaitons juste avoir une glace)

Si la glace elle meme avait été dépendante d'autres objets (disons, des noix de coco par exemple), c'est le rôle du conteneur que de résoudre l'ensemble des dépendances, dans le bon ordre.
laissant la tâche de la gestion des dépendances et des objets la plus simple possible pour le developeur (vous!). 

Concepts logiciels
-----------------

Now that you're fluent with dependency injection and inversion of control, 
we can start to talk about **how** to make a dependency injection container.

Concepts exposed here are simple, provides a structure for the container, 
and allow us to see clearly what is the good place and role of each class 
we made.

### The Schema representation
![The Schema, with services, methods and arguments](articles/dependency-injection/schema.png)

In the Schema, and in the DI in general, a "service" is an object managed by the
depency injection container. As said in the precendent section, the Schema 
represents the way services and classes are linked together. It describes the 
dependencies of our classes.

If you know the [abstract factory pattern](http://en.wikipedia.org/wiki/Abstract_factory), 
you can see the schema as a confguration when the container is the 
factory itself (or a kind of).

Schema contains all informations about methods we have to call in order to 
inject our objects, argument we have to inject, and all other information 
useful at the injection time.

In our example, the schema will contain information on wich `Icecream`  `Alice` 
depends, and wich is the way to provide the good `Icecream` to the her 
(the `setIcecream` method).

As far as now, we have talked about basics dependency rules. The Schema can 
handle many different types of Services, Methods and Arguments. 

We tried to facilitate the extention steps, to create and extend these types easily. 
All code we wrote is only dependent on a set of interfaces. So, it's possible to use 
any type of methods, arguments or services. They just have to implement 
the right interface.

Here is the tree type of interfaces existing in the Schema: Services, 
Methods and Attibutes.

#### Services

A service represents an object. Here, the Icecream is a Service, and Alice
is another one.
A service is composed by:

* a name
* a set of methods
* a way to be build
* a scope

Scope is the way to control the life cycle of our object: when requesting the 
service more than one time, what we have to do ? Use the first builded service?
Recreate one? Check in session if we already have one ? Scope tells us.

Our DI container comes with a set of different services types:

Default:
	A simple service, composed by methods, and wich can be built as a simple 
	object.

Aliases:
	A service wich is an alias for another one. Just the name is different. It
	allows us to manage our dependencies in the time. "For now, it's an alias,
	but maybe, one day,(haha) it'll be another type of service".
	
Inherited services:
	Rather than repeating ourselves multiple times, we can use
	inheritance in our service definition. It's just like inheritance in 
	programming: all methods and services you redefine or add inside this
	service will override the inherited services.

#### Methods

Each services contains Methods.

Methods are used to inject some parameters in our services, or define some 
ressources wich have to be called at the construction time. In the Alice 
exemple, one method is setIcecream.

A method is composed by:

* a name, 
* an optionnal classname
* a set of arguments
* information describing if it's static or not

Here is the different types of implemented methods:

Default:
	Simple method, with arguments.

Attribute methods:
	Used to directly set public attributes `$service->attribute = $value`. 
	This type of method can contain only one argument. 
    It can appear weird to manage attributes like methods. It's important 
    to understand the difference between methods and arguments. Arguments 
    represents values whereas methods represents ways to set them.

Callbacks:
	Before, or after the creation of your service, you can call specific 
	methods, called callback methods.

#### Arguments

Methods contains arguments, and there are different types of arguments. 
Arguments are the end of the chain service / method / argument. 
Argument contains values, that are standard native PHP types.

Here are the different types of arguments:

Default: 
	Native php types (int, string, float etc.)

Container Argument:
	This one represents the container itself. This option is used
	only for services that needs to use the container. These services
	are called "ContainerAware" services.

Current Service Argument:
	It's possible to use the injected service as an argument. In practice, 
	it's just used in callbacks methods, that need to be notified after
	the creation of a service.

Empty Value Argument:
	A argument wich has no special value (container argument and service
	argument extends this one)

Service Reference Argument:
	One of the most used type of argument. It represents another service.

Service Resolved Argument:
	Sometimes, it's useful to use another service method to get a argument.
	Think about configuration for exemple. 
	This type of argument relies on another service method to be resolved.


### Construction strategies

![Construction strategies](articles/dependency-injection/construction.png)

Now that we have a Schema representation of our objects, we have to build
(construct) them.

We've choosen to separate completely the construction logic and the definition
logic. Schema is a definition step, and building our services, and injecting
them is a construction step.

Each Schema type relies on a construction strategy. There are as many types
of construction strategies as schema definition types. (eg. Services, methods
and arguments)

Each definition type can build itself, calling the `build` method. In fact, 
internally, it's possible to build each schema type with different construction
strategies. This way of processing allows us (and you!) to easily add new
construction ways, with very tiny classes.

### Builders

Defining the Schema with objects and classes is not really "cool", and it can
take some time to describe each argument, service, method, by writing class calls
etc.

![Builders](articles/dependency-injection/builders.png)

To avoid this anonying behavior, an interesting way to proceed is to provide 
and use builders. Builders are objects that can read specific Schemas formats 
to build the Schema representation (with objects) wich is understandable
for us (and for the builder).

The first type of builder coming to my mind, is the XML builder. It can read
XML Schemas, and provide the good type of Schema. XML builder comes with a 
XSD definition file.

Some Java dependency injection container 
([Google Juice](http://code.google.com/p/google-guice/) and 
[Spring](www.springsource.org) uses annotation in the code to interact with 
the container. 

Annotations are text, in comments, wich provide information on what needs to be
injected, and how. 

Whereas it's not the default and recommended behavior, it'll be possible in 
Spiral's DI to generate a Schema representation thanks to these 
annotations.

We can imagine any other types of builders for the Schema.

The DI comes with theses dumpers:

* XML Builder
* PHP Builder (with a fluent interface)
* Annotations (uses reflection to get annotation in classes and build the schema)

### Dumpers

On the other side, it can be useful to use information provided by schema in
order to create other types of contents.

![Dumpers](articles/dependency-injection/dumpers.png)

It's possible to write the Schema thanks to a specific Builder, and to dump it
in another format. Our DI comes with an interesting dumper, that allows us to 
dump the schema in a graphic representation.

It's easy to show the dependencies of your application, by simply calling the
DotDumper (Dot is the format used by [graphviz](www.graphviz.com)).

Here is the list of built-in dumpers:

* Text dumper
* Dot Dumper
* XML Dumper

Implementation
---------------
Then, we knew how we wanted to architecture our component but we didn't knew where to 
start.

Here are the specificities and different steps we passed in when realising 
this component.

### Namespaces / PHP 5.3
When we started to think on this project, php 5.3 was not yet available but, 
because this version comes with some really interestant features (name it a few : 
late static binding and namespaces -- yes, these features are present since the dawn of ages
in other languages)

The Dependency Injection container is separated into the folowing namespaces:

* The `Construction` namespace, wich contains all construction related classes 
(the construction strategies)
* The `Definition` namespace,  wich contains the Schema.
* The `Dumpers` namespace, wich contains Dumpers
* The `Builders` namespace, wich contains Builders

### Test driven developement (TDD)
With this project, I've created my first tests, and try to follow a Test Driven 
Developement approach.

TDD says that you have to write your tests *before* startgin coding your 
classes. At the beginning, I've been a bit upset, but it's a really good software
dev. practice: Writing your tests before your classes requires to fix the API, 
and, because you're using your code just as you want to use it (and not as 
it have to be used, once the implementation done), you finally have a good and
usable API for your classes.

Making these tests before coding the classes has another interest: we think about
test scenarios we wouldn't have imagined otherwise. It requires us to **think**
all possible scenarios.

Another good reason to do this, is that writing tests after writing the classes 
is a little boring...

As we use inversion of control, all classes we made are simple to test thanks
to [mocks objects](http://en.wikipedia.org/wiki/Mock_object).

### Writing classes
To write classes, and because we wanted to provide an easy and extendable system, we 
almost systematically provided an Interface and an Abstract class for 
each concept that can be extended.

Writing classes is really simple once the architecture is clear. You can have
a look on my code on the [spiral's mercurial repository](http://bitbucket.org/ametaireau/spiral/src/)

There isn't a lot of things to say about it, except maybe if you don't already:
comment, comment comment your code, it's really an important thing to think
about guys who want to understand how all of this works

Conclusion
-----------
I hope this article has brought to you some interest on how works a dependency
injection container - especially this one - and drawed your interest
in using some good practices within your projects. If you want to discuss about it, 
feel free to contact me at alexis at supinfo dot com.
