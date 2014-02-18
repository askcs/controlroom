# Readme

## Setup

- Checkout the project
- Run it on a (local) server with PHP enabled
- Goto /login.php

Use a XMPP account on the xmpp.ask-cs.com to login. The script will (currently) only find out the PersonalAgent if it's on that XMPP domain. Via the PersonalAgent the DomainAgent and AlarmAgent are found. Since there are currently no real callbacks in the JS XMPP library (cc.call callbacks are not working), this script is using time intervals between XMPP calls which depend on each other (yup, very bad...). By default it will execute the 3 initial XMPP calls (discover agents) after 850ms each. If you check the 'Slow connection' checkmark on the loginscreen this value will be highered to 1800ms.

## Customize

Change the PHP constants SHORT_NAME and FULL_NAME to change the names in the interface

You can use bootstrap 'drop-in' themes (CSS files) to almost instantly change the style of the whole web interface

## Known limitations

- Can only be used for the xmpp.ask-cs.com (if not changed)
- Currently using a Google Map, but not all alarms send their location (Currently: location is ignored when such an alarm comes in, a notification is added to the alarm feed that no alarm location was given)
- cc.call() callbacks are not being triggered from the cape.js library:
- This makes it currently necassary to call a method, save the method name you called, and check in the general msgHandler function if the incoming response is indeed the response of your request.
- This also means that it's currently not possible to start multiple calls at once. Call -> wait for result. Call -> wait for result. (Incoming data, just send/pushed by the server, without requesting it, will still come in right after each other)
