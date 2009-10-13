This article is based on personal experience and investigation I had to do during 
the realisation of a piece of software for my personal use. I've really 
learned great things while working on this, and I want to share it.

This article talks about the Spiral's DI Container, shipped with a homemade 
framework I am working on with friends, in order to learn how *all of this* works:
Spiral.

The DI container is also available as a standalone version. You can find the code 
[on it's mercurial repository](http://bitbucket.org/ametaireau/spiral/). 

The current version of Spiral DI component is not complete (as of sept. 09)
but is in an advanced state. and will be released as of nov. 09.

Introduction
-------------

We'll talk about dependency injection, and especially about the thinking we had 
while working on this software. 

This article aims to be accessible by people who don't fully understand dependency
injection as well as confirmed users of the concept.

We'll try to talk about software good practices, and software architecture in 
general. While making this software component, our first goal was to learn, and
to discover how DI really works. Re-invent the wheel, in order to learn more about wheels. 

This document is not a documentation about how we can use Spiral DI, but on 
how we *made* it.

All exemple provided will be in PHP, but concepts that are discussed here
can be (and are !) implemented in other languages.

So, let's talk about dependency injection !


How do we currently manage objects
----------------------------------

First of all, we have to explain, briefly, what is inversion of control. Start 
with our daily bread: how we actually manage our objects.

![Icecreams?](articles/dependency-injection/icecream.png)

When making sofware in an object oriented way, we have to deal with classes, 
and to make theses classes interact. In practice, some of your classes are 
dependent on others.

All along this document, we'll keep a simple exemple : Let's imagine we 
are Alice, a young girl who loves eating icecream. We really love icecream, 
and especially those with strawberry flavour. 

Let's say that Alice is dependent on Strawberry ice cream.
	
	class Alice {
		
		public function eatIcecream(){
			$iceCream = new StrawberryIcecream();
			$iceCream->eat();
		}
	}
	
When Alice eats an icecream, she always choose the strawberry one. Great, but, 
one day, her mum wants Alice to try other tastes! 

Actually, with this code implementation, it's not possible to change 
the eaten icecream.

Inversion of Control (IoC)
--------------------------

### Principle

![Don't call me, I'll call you!](articles/dependency-injection/holywood-principle.png)

So, we need to remove dependencies between our two objects, in order to let Alice eat
other icecream tastes. How ? Have a look at the code:

	class Alice {
		
		public function eatIcecream(IceCream $icecream){
			$iceCream->eat();
		}
	}	

As you can see, when Alice eats icecream (when calling the `eatIcecream` 
method), we have to pass her the wanted `Icecream`. So, it's not Alice that
chooses the taste of her icecream anymore, *we* do.

You can remember this as the **Hollywood principle** : "_don't call me, I'll 
call you_", or in other terms, dont use the `new` operator to create your 
objects inside your classes, but passes all your objects, by reference.

Alice can do some other things with this icecream, let it fall down,
for instance, (thanks to the `releaseIcecream` method). We can chose to pass 
the icecream to this method, or to inject the ice cream directly to Alice,
letting her doing the stuff she wants.

	class Alice {
		protected $_icecream = null;
		
		public function setIcecream(IceCream $icecream){
			$this->_icecream = $icecream;
		}

		public function eatIcecream(){
			$this->_icecream->eat();
		}

		public function releaseIcecream(){
			$this->_icecream->release();
		}
	}	

Here, we can choose which taste is good for Alice, and it's easier to 
control Alice's icecream dependencies.

That's all for the inversion of control principle: its just the fact of inverting the 
control flow of your applications by delegating at a higher level 
the creation of objects.

### Dependency injection

Ok, now that the concept of inversion of control is clear in your head, it makes 
more sense to explain what is the dependency injection.

In the `eatIcecream` method, we consider that our icecream object is already 
given to Alice, it's really a useful behavior: we don't matter about how to get
the icecream anymore, we already have it (as a private property for exemple)
 
In the precedent section, Alice was _dependent_ on the Icecream. 

By inverting the control flow, Alice behavior is now more testable (using mock 
objects is now really simple, as setting a parameter, using setters, we'll 
talk about tests later). 

Now, our job (the Mum's one!) is to create and pass the icecream to Alice.
To _inject_ is the correct word. Dependency injection is just that: call your 
setters or constructors, injecting the right objects when needed. 
So, let's go ! 

Mum's action:

	$alice = new Alice();
	$bananaIcecream = new BananaIcecream();
	$alice->setIcecream($bananaIcecream);

### A dependency injection container ?

The above example is voluntarily simple, to expose the concepts clearly:
we just have two classes, and one dependency. 

In important projects, with lot of classes and dependencies, handling
objects lifecycle can become a hard work !

The dependency injection task can be automatized, and it's the aim of a 
dependency injector container.

Why "container" ? Because the automatic creation and injection is done
thanks to a container, wich contains all dependencies information.

Still with the same example, the dependency injection container will do the injection, 
on its own. It'll do Mum's work for us.

The final behavior we want to achieve, is that when calling alice, she comes with 
an already eat-ready injected icecream !

	$alice = $container->getService('Alice');
	$alice->eatIceCream();

Here, the container had succefully given the right icecream to Alice (no matter 
wich one, we just want to deal with icecreams !)

If the icecream itself has been dependent on another object (let's say .. peanuts)
it's the container's role to resolve, in the right order, all dependencies, 
keeping the object management simple for the developer (you!).

Software concepts
-----------------

Now that you're fluent with dependency injection and inversion of control, 
we can start to talk about **how** to make a dependency injection container.

Concepts exposed here are simple, provide a structure for the container, 
and allow us to see clearly what is the good place and role of each class 
we made.

### The Schema representation
![The Schema, with services, methods and arguments](articles/dependency-injection/schema.png)

In the Schema, and in the DI in general, a "service" is an object managed by the
depency injection container. As said in the precedent section, the Schema 
represents the way services and classes are linked together. It describes the 
dependencies of our classes.

If you know the [abstract factory pattern](http://en.wikipedia.org/wiki/Abstract_factory), 
you can see the schema as a configuration when the container is the 
factory itself (or a kind of).

Schema contains all information about methods we have to call in order to 
inject our objects, arguments we have to inject, and all other information 
which is useful at the injection time.

In our example, the schema will contain information on wich `Icecream`  `Alice` 
depends, and wich is the way to provide the good `Icecream` to her 
(the `setIcecream` method).

As far as now, we have talked about basics dependency rules. The Schema can 
handle many different types of Services, Methods and Arguments. 

We tried to facilitate the extension steps, to add new types easily. 
All code we wrote is only dependent on a set of interfaces. So, it's possible to use 
any kind of methods, arguments or services. They just have to implement 
the right interface.

Here are the three interfaces that the Schema is dealing with: Services, 
Methods and Attibutes.

#### Services

A service represents an object. Here, the Icecream is a Service, and Alice
is another one.
A service is composed by:

* a name
* a set of methods
* a building process
* a scope

Scope is the way to control the life cycle of our services in the container: 
Does the service remain in the container during the whole script (singleton) or 
is it instantly removed from it (prototype)?
The actual instance of the injected object must be the same for all services if 
the scope is defined as singleton, or each time a different one if the scope is set 
to prototype.

Other scopes could be imagined like the "session" scope that would provide the same instance 
through a unique session, or a kind of "immortal" scope that would use persistence features to 
make an instance immortal through user sessions.

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

Each service contains Methods.

Methods are used to inject some parameters in our services, or define some 
ressources wich have to be called at the construction time. In the Alice 
exemple, one method is setIcecream.

A method is composed by:

* a name, 
* an optionnal class name
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

To avoid this anoying behavior, an interesting way to proceed is to provide 
and use builders. Builders are objects that can read specific Schemas formats 
to build the Schema representation (with objects) wich is understandable
for us (and for the builder).

The first type of builder coming to my mind, is the XML builder. It is able to 
read schemas defined in an XML based syntax, and provide the good type of Schema.
Our XML builder comes with a XSD definition file.

Some Java dependency injection container 
([Google Juice](http://code.google.com/p/google-guice/) and 
[Spring](www.springsource.org) uses annotation in the code to interact with 
the container. 

Annotations are text, that's fit to the class implementation wich provide information on what needs to be
injected, and how.

Whereas it's not the default and recommended behavior, it is possible in 
Spiral's DI to generate a Schema representation thanks to these 
annotations.

We can imagine any other types of builders for the Schema.

The DI comes with theses builders:

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
Then, we knew how we wanted to architecture our component but we didn't know where to 
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
* The `Transformation` namespace, wich contains Builders and Dumpers.

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

### Interfaces

In all our classes, we try the most to deal with interfaces, and not with specific
implementations. why ? because dealing with interfaces allows us to switch the wanted
implementation at each moment.


All these interfaces represents a behavior described bellow.

* Schema
* Service
* Method
* Argument
* Container
* Dumper
* Builder

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
injection container - especially this one - and egg you on 
using some good practices within your projects. If you want to discuss about it, 
feel free to contact `alexis at supinfo dot com`
