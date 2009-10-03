How to make a good dependency injection container? Software good practices
===========================================================================

This article, which is going to talk about many subjects, is based on a personal experience and investigations I had during the realisation of a piece of software, for my personal use. No more suspence, this software is a dependency container, used in a web framework project, as a main piece.


Aims of this article
---------------------

In this article, I'll talk to you about dependency injection, and especially, how to build a succesfully and easy extendable dependency injection container. This article will be divided into 5 parts. It's important to understand each part, in is totality.

I will talk about software architecture in general, but, also about good practices, and it can change the way you code your applications. In fact, if you don't already use inversion of control pattern, it **will** do change the way you code. Maybe am I a little optimistic ? Let's see !


How do we actually manage objects
----------------------------------

First of all, we have to explain, briefly, what is inversion of control, in order to completely understand all things we are gonna do.
When you are making sofware, in an Object Oriented way, you create classes that will interact. 

A common practice is to call some classes from others. You'll understand quickly what is inversion of control.

Let's imagine we have a `Car` class, wich is dependent on a tire `MichelinTire` object. Here is an exemple of dependent code:
	
	class RedCar {
		protected $_tire = null;
		
		public function __construct(){
			$this->_tire = new MichelinTire();
		}
	}
	
When building the Car, constructor build for us the tire, and return us a great car, with the Tires already build in.

##### This includes dependencies !
The problem that appears is simple: our `Car` is now dependent on `MichelinTire`s. If, for instance, we want to put on another brand of `Tire`s, we can't ! 


Inversion of control
---------------------

##### Principle

![Don't call me, I'll call you!](articles/dependency-injection/holywood-principle.png)

The solution to this problem is simple, we need to remove the dependency between our two objects.

	class RedCar {
		protected $_tire = null;
		
		public function __construct(Tire $tire){
			$this->_tire = $tire;
		}
	}	

As you can see, the `Tire` is given in the constructor of our `Car`, and no more builded into the constructor. You can remember this as the **Hollywood principle** : _don't call me, I'll call you_, or in other worlds, dont use the `new` operator to create your objects inside our classes, but passes all your objects via constructors, or via setters.

Here is an exemple using setters:

	class RedCar {
		protected $_tire = null;
		
		public function setTire(Tire $tire){
			$this->_tire = $tire;
		}
	}	
	
Maybe have you noticed that the `setTire` method is waiting for a `Tire`, and not for the special `MichelinTire`. You're right, and it allows us to be dependent, not on a special implementation of a Tire, but on **all** possible implementations of `Tire`s. In fact, `Tire` is the _interface_ of all our Tire.

That's all for the inversion of control principle: it just invert the flow of control of your applications by delegating at a higher level the creation of objects.

##### Dependency injection

Ok, now that the concept of inversion of control is clear in your head, it make more sense to explain what is the dependency injection.

In the precendent section, the `Car` was _dependent_ on the `Tire`. By inverting the flow of control, our class is now more testable (using mocks objects is now really simple, as setting a parameter, using setter), but we have to create our `Tire`, and inject it into our `RedCar` object.

To _inject_ is the main word. Dependency injection is just that: call our setters or constructors. So, let's go !

	$myTire = new MichelinTire();
	$myCar = new RedCar();
	$myCar->setTire($myTire);
	

##### A dependency injection container ?

In our case, it's really really simple, because we have just two classes, and one dependency. In important projects, with tons of classes and tons of dependencies, managing object lifecycles can become a hard work !
The dependency injection task can be automatised, and this is the aim of a dependency injector.	

Why "container" ? Because the principle of automatizing theses creation and injection task is done thanks to a container, wich contains all relation schema. 

Always with the same exemple, the dependency injection container will do the injection, on his own.

Here is some important concepts that I've used when realising this software.


The concepts
------------

##### The Schema representation

Schema represents the way object and classes are linked together. It's this schema the most important thing in the overall concepts.

The Schema contains all informations about methods we have to call to inject our objects, argument types, and all other information that can be useful to know at the injection time.

In our exemple, the schema will contain information on wich `Tire` the `Car` is dependent, and wich is the way to provide the good `Tire` to the `Car` (let's say by setter).

###### Services

###### Methods

###### Arguments


##### Construction strategies

I've choosen to separate completely 

##### Builders

Builders, are classes that are capable of building a schema object from other forms of schema, like XML, or YAML, or, why not, plain text, that are more human comprehensive.


##### Dumpers


Implementation
---------------


##### Namespaces / PHP 5.3

##### Test driven developement

##### Writing classes


One step further: how to extends the Container ?
-------------------------------------------------

Now that we know what we have to make, let's reflect on how this can be an useful tool in our future developements. The Dependency Injection is, when we're thinking about, just like a big [abstract factory][]. 
It build objects and call some methods
