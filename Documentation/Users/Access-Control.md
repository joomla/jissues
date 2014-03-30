## Access Control (ACL)

ACL is limited and very specific:

* Only **Projects** are tracked.
* There are only 5 "hard wired" **Actions**:<br />`view`, `create`, `edit`, `editown` and `manage`.
* It is based on **groups** a user automatically belongs to or can be assigned to.<br />
For every project two **system groups** are created by default:<br />`Public` and `User`.

The special **admin user** role that is assigned using the `config.json` file is granted global access.

Following is an example setup for a security tracker with non public access and two additionally created custom groups:

![acl-projects-groups](https://cloud.githubusercontent.com/assets/33978/2562602/c5722320-b855-11e3-9157-640c8ec68bce.png)

Note that if you have `Edit` permissions, you have automatically `Editown` permissions.

However, this is a very first step... lots of optimization and testing required here.
