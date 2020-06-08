# Exhibitors Code System

Another Wordpress plugin... which allows you to generate automatically or manual codes for exhibitors and more.

## Getting Started

Make sure You are using Gravity Forms and Wordpress 4.9+

### Installing

First of all You need to make form in Gravity Forms plugin. 

#### Creating a form for check entered code before access to form. 

Create new form in Gravity Forms and add that one field to form's fields:

```
type = Single Line Text
```

And add class:

```
invitation_code
```

* Add this field in confirmations for Form admin.

Thats all in Gravity Forms, now create a new Wordpress Page and set 'Page Template' to 'Exhibitors Code Checker'. Then insert in Your page content form You created in Gravity Forms.

Enjoy!

#### Creating a form for automaticaly generate invitation code for exhibitors. 

Create new form in Gravity Forms and add that one field to form's fields:

```
type = Single Line Text
```

And add class:

```
code
```

* Add this field in confirmations for exhibitor.

Go to plugin settings and set prefix for automatically generating exhibitors invitation code. For example:

```
XYZ2102
```

* Individual Code for Exhibitor is generating this way: 'prefix_you_set'+'amount of exhibitors in current form base'. For example:
```
XYZ21021
```

Set Your Grafity Form ID that You want to generate codes.

* Optional: Set Your own list of codes comma separated - **no comma at the end**

Thats all in Gravity Forms, now create a new Wordpress Page and set 'Page Template' to 'Exhibitors Code Maker'. Then insert in Your page content form You created in Gravity Forms.

Enjoy!

* Don't forget about link to Your Page Checker in notification for exhibitor.

## Built With

* [Gravity Forms](https://www.gravityforms.com/) - The Wordpress Plugin
* [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) - Update Checker for Wordpress Plugins and Themes
* [jQuery](https://jquery.com/) - JavaSript Library

## Authors

* **Szymon Kaluga** - [github](https://github.com/szymoff) - [website](http://skaluga.pl) 