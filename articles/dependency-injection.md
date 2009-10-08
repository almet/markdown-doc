
This article is based on personal experiences and investigations I had during 
the realisation of a piece of software, for my personal use. I've really 
learned great things when working on it, and want to share it.

This article talks about the Spiral's DI Container, shipped with a homemade 
framework I made with friends, in order to learn how *all of this* works:
Spiral. 

The DI container is also available as a standalone version. You can find the code 
[on it's mercurialrepository](http://bitbucket.org/ametaireau/spiral/). 

The current version of this article describe is not yet finished (as of sept. 09)
but is in an advanced state. and will be released as of nov. 09.

Introduction
-------------

We'll talk about dependency injection, and especially about reflections we had 
while working on this software. 

This article aims to be read by people who don't fully understand what is 
dependency injection, but also by people fluent whith this concept.

We'll try to talk about software good practices, and software architecture in 
general. When making this software component, our first goal was to learn, and
to discover how this works. Re-invent the wheel, in order to learn more about
on wheels. 

This document is not a documentation about how we can use Spiral DI, but on 
how we have *made* it.

All exemple provided will be in PHP, but concepts we are talking discussing
can be (and are !) implemented in other languages.

So, let's talk about dependency injection !


How do we currently manage object
---------------------------------

First of all, we have to explain, briefly, what is inversion of control. Start 
with our daily bread: how we actually manage our objects.

When making sofware, in an object oriented way, we have to deal with classes, 
and to make theses classes interact. In practice, some of your classes are 
dependent on from others.

All alongthis document, we will keep a simple exemple : Let's imagine we 
are Alice, a young girl wich loves eating icecreams. We really love icecreams, 
and especially those with strawberry flavour. 

Let's say that Alice is dependent on Strawberies ice creams.
	
	class Alice {
		
		public function eatIcecream(){
			$iceCream = new StrawberryIcecream();
			$iceCream->eat();
		}
	}
	
When Alice eats an icecream, she always choose the strawberry one. Great, but, 
one day, her mum want's Alice to try other tastes! 

Actually, with this code implementation, it's not really possible to change 
the eaten icecream.

Inversion of Control (IoC)
--------------------------

### Principle

![Don't call me, I'll call you!](articles/dependency-injection/holywood-principle.png)

So, we need to remove dependencies between our two objects, in order to let Alice eat
other icecreams tastes. How can we made this ? Have a look at the code!

	class Alice {
		
		public function eatIcecream(IceCream $icecream){
			$iceCream->eat();
		}
	}	

As you can see, when Alice eat icecream (when calling the `eatIcecream` 
method), we have to pass her the wanted `Icecream`. So, it's not Alice that
choose the taste of her icecream, *we* do.

You can remember this as the **Hollywood principle** : "_don't call me, I'll 
call you_", or in other terms, dont use the `new` operator to create your 
objects inside your classes, but passes all your objects, by reference.

Alice can do some other things with this icecream, let fall it down,
for instance, (thanks to the `releaseIcecream` method). We can chose to pass 
the icecream to this method, or to inject the ice cream directly to Alice,
letting her making the stuff she wants with.

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

Here, we can choos wich taste is good for Alice, and it's easier to 
control Alice's icecreams dependencies.

That's all for the inversion of control principle: its just the fact of inverting the 
control flow of your applications by delegating at a higher level 
the creation of objects.

### Dependency injection

Ok, now that the concept of inversion of control is clear in your head, it makes 
more sense to explain what is the dependency injection.

In the `eatIcecream` method, we consider that our icecream object is already 
given to Alice, it's a really useful behavior: we don't worry about how to get
the icecream anymore, we already have it (as a private property for exemple)
 
In the precendent section, Alice was _dependent_ on from the Icecream. 

By inverting the control flow, Alice behavior is now more testable (using mock 
objects is now really simple, as setting a parameter, using setter, we'll 
talk about tests later). 

Our job (the Mum's one!) is to create and to pass the icecream to Alice.
To _inject_ is the main word. Dependency injection is just that: call your 
setters or constructors, injecting the right objects when needed. 
So, let's go ! 

Mum's action:

	$alice = new Alice();
	$bananaIcecream = new BananaIcecream();
	$alice->setIcecream($bananaIcecream);

### A dependency injection container ?

Exemple I took is voluntarily simple, to expose the concepts. 
We just have two classes, and one dependency. 

In important projects, with tons of classes and tons of dependencies, managing 
object lifecycles can become a hard work !

The dependency injection task can be automatised, and this is the aim of a 
dependency injector container.

Why "container" ? Because the automatic creation and injection is done
thanks to a container, wich contains all dependencies information.

Always with the same exemple, the dependency injection container will do the injection, 
on its own. It'll do Mum's work for us.

The final behavior we want to achieve, is that when calling alice, she comes with 
an already injected icecream, ready to eat !

	$alice = $container->getService('Alice');
	$alice->eatIceCream();

Here, the container had succefully given the right icecream to Alice (no matter 
wich one, we just want to deal with icecreams !)

If the icecream itself has been dependent on another object (let's say .. pinuts !)
It's the container's role to resolve, in the right order, all dependencies, 
keeping the object management simple for the developer (you!).

The sofware concepts
--------------------

Now that you've fully understand what is dependency injection and inversion of control, 
we can start to talk about **how** to make this dependency injection container.

Concepts exposed here are simple concepts, and provide a structure for the 
container, and allow us to see clearly what is the good place and role of 
each class we made.

Some Java dependency injection container uses annotation in the code to interact
with the container. Here, whereas it's not the default and recommended behavior,
it'll be possible. 

In fact, it's possible to generate a Schema representation thanks to theses 
annotations.

### The Schema representation

In the Schema, and in the DI in general, a "service" is an object managed by the
depency injection container.

As said in the precendent section, the Schema represents the way services and 
classes are linked together. It describe the dependencies of our classes.

If you know the abstract factory design pattern, the schema represents a sort
of confgiguration for this abstract factory, when the container is the factory
itself (or a sort of).

Schema contains all informations about methods we have to call in order to 
inject our objects, argument we have to inject, and all other information 
useful at the injection time.

In our exemple, the schema will contain information on wich `Icecream`  `Alice` 
depends, and wich is the way to provide the good `Icecream` to the her 
(the setIcecream method).

As far as now, we have talked about basics dependency rules. The Schema can 
handle many different types of Services, Methods and Arguments. In fact, 
the method we choosen allow to extend the types easily. Because we wrote code
that is on ly dependent on interfaces, it's possible to use any type of methods,
the only condition is they have to implement oru interfaces.

Here is the tree type of interfaces existing in the Schema:

#### Services

A service represents an object, so, here, the Icecream is a Service, and Alice
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
	allow us to manage our dependencies in the time. "For now, it's an alias,
	but maybe, one day,(haha) it'll be another type of service".
	
Inherited services:
	Rather than repeating ourselves times and times, we can use
	inheritance in our service definition. It's just like inheritance in 
	programming: all methods and service you redefine or you add inside this
	service will override the inherited service ones.

#### Methods

Each services contains Methods.

Methods are used to inject some parameters in our services, or defines some 
ressources wich had to be called at the construction time. In the Alice's 
exemple, one method is setIcecream.

A method is composed by:

* a name, 
* an optionnal classname
* a set of arguments
* information describing if it's static or not

Here is the different type of implemented methods:

Default:
	Simple method, with arguments.

Attribute methods:
	Used to directly set public attributes `$service->attribute = $value`. 
	This type of method can only contain one argument.

Callbacks:
	Before, or after the creation of your service, you can call specific 
	methods, called callback methods.

#### Arguments

Methods contains arguments, and there is different types of arguments. 
Arguments aer the end of the chain service / method / argument. 
Argument contains values, that are standard native PHP types.

Here is the different types of arguments:

Default: 
	Native php types (int, string, float etc.)

Container Argument:
	This one represents the container itself. This option is used
	only for services wich needs to use the container. This services
	are called "ContainerAware" services

Current Service Argument:
	It's possible to use the injected service as argument, in practice, 
	it's just used in callbacks methods, that need to be notified after
	the creation of a service.

Empty Value Argument:
	A argument wich had no special value (container argument and service
	argument extends this one)

Service Reference Argument:
	One of the most used type of argument. It represents another service.

Use Reference Argument:
	Sometimes, it's useful to use another service method to get a argument.
	Think about configuration for exemple. 
	This type of argument relies on another service method to be resolved.


### Construction strategies

Now that we have a Schema representation of our objects, we have to build
(construct) them.

We've choose to separate completely the construction logic and the definition
logic. Schema is a definition step, and building our services, and injecting
them is a construction step.

Each Schema type relies on a construction strategy. There are as many types
of construction strategies as schema definition types. (eg. Services, methods
and arguments)

Each definition type caxn build itself, calling the `build` method. In fact, 
internally, it's possible to build each schema type with different construction
strategies. This way of processing allows us (and you!) to easily add new
construction ways, with very tiny classes.

### Builders

Defining the Schema with objects and classes is not really "cool", and it can
take some time to describe each argument, service, method, by writing class calls
etc.

To avoid this anonying behavior, an interesting way to proceed is to provide 
and use builders. Builders are objects which can read specific format Schemas, 
to build the Schema representation (with objects) wich is comprehensive
for us (and for the builder).

The first type of builder wich comes to my mind, is the XML builder. It can read
XML Schemas, and provide us the good type of Schema. XML builder comes with a 
XSD definition file.

We can imagine any other types of builders for the Schema.

The DI comes with theses dumpers:

* XML Builder
* PHP Dumper

### Dumpers

On the other side, it can be useful to use information provided by schema in
order to create other types of contents.

It's possible to write the Schema thanks to a specific Builder, and to dump it
in another format. Our DI comes with an intersting dumper, that allows us to 
dump the schema in a graphic representation.

It's easy to show the dependencies of your applications, by simply calling the
DotDumper (Dot is the format used by [graphviz](www.graphviz.com)).

Here is the list of built-in dumpers:

* Text dumper
* DotDumper
* XML Dumper

Implementation
---------------
As we know how we want to architecture our component, we didn't know how to 
start.

Here are the specificities and different steps we passed in when realising 
this component.

### Namespaces / PHP 5.3
When started to reflect on this project, php 5.3 was not yet available, but, 
because this version comes with some really interestant features (I think at
late static binding and namespaces -- yes, theses features are from long time
in another languages).

The Dependency Injection container is separated into the folowing namespaces:

* The `Construction` namespace, wich contains all construction related classes 
(the construction strategies)
* The `Definition` namespace,  wich contains the Schema.
* The `` namespace, wich contains Dumpers and Builders.

### Test driven developement (TDD)
With this project, I've created my first tests, and try to follow a Test Driven 
Developement approach.

TDD says that you have to write your tests *before* startgin coding your 
classes. At the beginning, I've been a bit upset, but it's a really good software
dev. practice: Writing your tests before your classes require to fix the API, 
and, because you're using your code just as you want to use it (and not as 
it have to be used, once the implementation done), you finally have a good and
usable API for your classes.

And, writing tests after writing the classes is a little boring, too...
So, all tests have been made before coding the classes. Same for new features. 

As we use inversion of control, all classes we made are simple to tests thanks
to mocks objects.

### Writing classes
For writing classes, because we want to provide an easy extandable system, we 
have almost systematically provided an Interface and an Abstract class.

Writing classes is really simple once the architecture is clear. You can have
a look on my code on the spiral's mercurial repository.

There isn't a lot of things to say about it, except maybe if you don't already
do: comment, comment comment your code !

One step further
----------------

If you're interested in 

Conclusion
-----------
I hope this article has bring to you some interest on how works a dependency
injection container, and especially this one. 
