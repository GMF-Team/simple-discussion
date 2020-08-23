# TYPO3 Extension `simple_discussion`

## What does it do?

This extension provides a simple discussion or commenting system.

## Installation

### Installation using Composer

Run the following command within your Composer based TYPO3 project:

```
composer require ???
```

### Installation using Extension Manager

Login into TYPO3 Backend of your project and click on `Extensions` in the left menu.
Press the `Retrieve/Update` button and search for the extension key `simple_discussion`.
Import the extension from TER (TYPO3 Extension Repository)

## Configuration

- Important: Include static template

__Adding Extbase RouteEnhancer__

```
  SimpleDiscussion:
    type: Extbase
    extension: SimpleDiscussion
    plugin: Plugin
    routes:
      -
        routePath: '/list/{instruction}'
        _controller: 'Comment::list'
      -
        routePath: '/new-comment/{instruction}'
        _controller: 'Comment::new'
      -
        routePath: '/create-comment/{instruction}'
        _controller: 'Comment::create'
      -
        routePath: '/update-comment/{instruction}'
        _controller: 'Comment::update'
      -
        routePath: '/edit-comment/{comment}/{instruction}'
        _controller: 'Comment::edit'
    defaultController: 'Comment::list'
    aspects:
      instruction:
        type: StaticValueMapper
        map:
          all: 'all'
          new: 'new'
          create: 'create'
          edit: 'edit'
          update: 'update'
```

__Use via typoscript__

```
# Setup:
# @import 'EXT:yourproject/folder/Comment.typoscript'

plugin.tx_simplediscussion_plugin {
	settings {
		noticeEmail = 1
		autoHide = 1
		allowReply = 1
		emailTo = email@company.de
		emailToName = Johnny
		emailFrom = webmaster@company.de
		emailFromName = Company
		emailSubject = Please approve Comment
	}
}

lib.comments = COA
lib.comments {
	10 = USER
	10 {
		userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run

		# taken from namespace: MyVendorName\MyExtName\
		extensionName = SimpleDiscussion

		# taken from plugin ext_localconf.php
		pluginName = Plugin

		# taken from controller class: Controllername (withour controller)
		vendorName = Gmf
		controller = Comment

    	# Set Default Action
    	action = list
	}
}
```
