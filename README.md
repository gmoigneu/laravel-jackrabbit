Laravel 5.x package providing Jackrabbit backend capabilities with Doctrine-PHPCR-ODM mappings.

## Prerequisites

- Apache Sling **7** or Apache Jackrabbit 2.3.6+

**[Jackalope](https://github.com/jackalope/jackalope-jackrabbit/) is currently not working with Jackrabbit Oak and thus not in Apache Sling 8**

Download Sling Launchpad 7 on [Maven repository](http://repo2.maven.org/maven2/org/apache/sling/org.apache.sling.launchpad/7/).

More info on : 
- https://sling.apache.org/news/sling-launchpad-8-released.html
- https://github.com/jackalope/jackalope-jackrabbit/issues/98#issuecomment-154832686




## Installation

	composer require gmoigneu/laravel-jackrabbit

## Configuration

Publish the config file & edit it with your Jackrabbit details:

    php artisan vendor:publish


## Usage

### JCR Session

```php
// Init session
$session = \App::make('phpcr.session');

// Save a new testNode
$rootNode = $session->getNode("/");
$testNode = $rootNode->addNode("testNode");
$session->save();

// Get the newly created node
$testNode = $session->getNode("/testNode");
dd($testNode);
```

## Document Manager

Create a new model :

```php
<?php namespace App\Models;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

/**
 * @PHPCR\Document(referenceable=true)
 */
class Post
{

    /**
     * @PHPCR\Uuid()
     */
    protected $uuid;

    /**
     * @PHPCR\Id()
     */
    protected $slug;

    /**
     * @PHPCR\ParentDocument()
     */
    protected $parent;

    /**
     * @PHPCR\NodeName
     */
    protected $title;

    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

}
```
    
Register your new type with :

	$ php artisan doctrine:phpcr:register-system-node-types                                                                     
	Successfully registered system node types.

Use your model wherever you want :

```php
// Get the document manager
$dm = \App::make('phpcr.manager');

// Get the root node
$root = $dm->find(null, '/');

// Create a post
$post = new Post();
$post->setParent($root);
$post->setTitle('Example Post');

$dm->persist($post);
$dm->flush();

$post = $dm->find(null, 'Example Post');
dd($post);
```

# Credits

Based on the work of [Workers](https://github.com/Workers/laravel-phpcr-odm)
