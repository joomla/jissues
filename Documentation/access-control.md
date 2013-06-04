## Access control
Acces control is limited and very specific:

* Only **Projects** are tracked.
* There are only 4 "hard wired" **Actions**:<br />`view`, `create`, `edit` and `manage`.
* It is based on **groups** a user automatically belongs to or can be assigned to.<br />
For every project two **system groups** are created by default:<br />`Public` and `User`.

The special **admin user** role that is assigned using the `config.json` file is granted global access.

Following is an example setup for a security tracker with non public access and two additionally created custom groups:
![Access groups](https://f.cloud.github.com/assets/33978/550822/fc7c42a0-c31a-11e2-82c8-85f4ea05b92e.png)

However, this is a very first step... lots of optimization and testing required here.
