# Introduction to Ansible
The Ansible website describes it as:
> Ansible is a simple automation language that can perfectly describe an IT application infrastructure

## Prerequisites
### Installing Ansible on OSX
First install pip if you have not already installed it
```bash
sudo easy_install pip
```
Then install the latest version of Ansible
```bash
sudo pip install ansible
```
### Start example vagrant boxes
To try the examples of this tutorial please start the vagrant boxes:
```bash
vagrant up
```
# Playbooks
A playbook is Ansibles scripting language for orchestrating and configuring servers.
## Running a Playbook
A playbook is in a yaml format making it easy to read and understand.

```yaml
- hosts: localhost
  tasks:
    - debug: msg='hello world'
```
We can run ```hello_world.yml` playbook with a simple command
```bash
ansible-playbook hello_world.yml
```
When you run the playbook you will see that it runs all the tasks in the playbook.
After all the tasks have been completed it will print out a summary of the results.
```
PLAY [localhost] ***************************************************************

TASK [setup] *******************************************************************
ok: [localhost]

TASK [debug] *******************************************************************
ok: [localhost] => {
    "msg": "hello world"
}

PLAY RECAP *********************************************************************
localhost                  : ok=2    changed=0    unreachable=0    failed=0  
```
# Inventory
An inventory describes all your servers in your infrastructure.
## Creating an inventory
An inventory is in the ini format which contains a heading to group a list of servers and then their host names:
```ini
[frontend]
192.168.0.32
192.168.0.33
192.168.0.34

[backend]
backend-server-1
backend-server-2

[database]
database-1 ansible_host=192.168.0.128
```
You can then set the hosts value in your playbook to run all the commands on those servers:
```yaml
- hosts: backend
  tasks:
    - debug: msg='hello world'
```
You can then run the playbook by specifying the inventory file to use:
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    hello_world_frontend.yml
```
## Vagrant Hosts
To use Ansible with Vagrant you need to set ```ansible_user``` and ```ansible_ssh_private_key_file``` in the inventory configuration
```ini
[frontend]
192.168.33.31 ansible_user=vagrant ansible_ssh_private_key_file=.vagrant/machines/machine1/virtualbox/private_key
192.168.33.32 ansible_user=vagrant ansible_ssh_private_key_file=.vagrant/machines/machine2/virtualbox/private_key
```
# Running Playbooks
## Sudo
To perform tasks that requires ```sudo``` you need to pass in the ```become``` option.
```yaml
- hosts: frontend
  become: true
```
You can run individual tasks with ```sudo``` as well by adding it to the task
```yaml
- hosts: frontend
  tasks:
    - name: Install Nano
      become: true
      yum:
        name: nano
      state: present
```
## Running On All Hosts 
We can install a package on all of our servers by setting hosts to ```all```.
```yaml
- hosts: all
  become: true
  tasks:
    - name: Install Nano
      yum:
        name: nano
        state: present
```
We can run this example to install a package on all our servers
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    install_server_software.yml
```
You will then see that the server packages are installed on all the servers.
```
PLAY [all] *********************************************************************

TASK [setup] *******************************************************************
ok: [frontend-2]
ok: [backend-1]
ok: [frontend-1]

TASK [Install Nano] ************************************************************
changed: [frontend-1]
changed: [backend-1]
changed: [frontend-2]

PLAY RECAP *********************************************************************
backend-1                  : ok=2    changed=1    unreachable=0    failed=0   
frontend-1                 : ok=2    changed=1    unreachable=0    failed=0   
frontend-2                 : ok=2    changed=1    unreachable=0    failed=0
```
## Running Playbooks On Some Servers
We can select groups to run the playbooks in by seeing the host to the group name.
```yaml
- hosts: frontend
  become: true
  tasks:
    - name: Install HTTPD
      yum:
        name: httpd
        state: present
```
You can run this with:
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    install_http_server.yml 
```
You will see that it will only install ```httpd``` on the frontend servers.
```
PLAY [frontend] ****************************************************************

TASK [setup] *******************************************************************
ok: [frontend-2]
ok: [frontend-1]

TASK [Install HTTPD] ***********************************************************
changed: [frontend-1]
changed: [frontend-2]

PLAY RECAP *********************************************************************
frontend-1                 : ok=2    changed=1    unreachable=0    failed=0   
frontend-2                 : ok=2    changed=1    unreachable=0    failed=0
```
# Variables
Ansible supports variables to make your scripts more portable.
We can use the ```vars``` to do this:
```yaml
- hosts: frontend
  become: true
  vars:
    webserver: httpd
    welcome:
      dest: /var/www/html/index.html
      content: 'hello world'
    packages:
      - nano
      - wget
```
We can use them to install the webserver:
```yaml
- name: Install Webserver
  yum:
    name: "{{ webserver }}"
    state: present
```
We can also access variables at different levels:
```yaml
- name: Insert a welcome page
  copy:
    dest: "{{ welcome.dest }}"
    content: "{{ welcome.content }}"
```
We can access an array of variables
```yaml
- name: Install Server Packages
  yum:
    name: "{{ item }}"
    state: present
  with_items: "{{ packages }}"
```

```bash
ansible-playbook \
    -i inventory/inventory.ini \
    configure_server.yml
```
Now we can view the [hello world page](http://192.168.33.31/) running on our webserver.
## Including Variables
We can provide configuration by passing a listing of files
```yaml
- hosts: all
  become: true
  vars_files:
    - vars/common.yml
```
We can now create ```vars/common.yml``` with the following content
```yaml
webserver: httpd
welcome:
  dest: /var/www/html/index.html
  content: |
    <html>
    <title>Ansible</title>
    <body>
    <h1>Hello World From Ansible</h1>
    </body>
    </html>
packages:
  - nano
  - wget
```
# Handlers
Handlers enable us to perform operations only on a change.
```yaml
handlers:
  - name: Start Webserver
    service:
      name: "{{ webserver }}"
      enabled: yes
      state: started
```
We can call this handler with ```notify``` with:
```yaml
- name: Install Webserver
  yum:
    name: "{{ webserver }}"
    state: present
  notify: Start Webserver
```
When the playbook is completed the handlers will be run if they are needed.
The handlers will be run once so we can call ```notify``` as many times as we want.
# Roles
Roles can be used to organise your playbooks better
## Playbook structure
```yaml
- hosts: all
  become: true
  vars_files:
    - vars/common.yml
  roles:
    - common
    - httpd
    - php
```
We can now structure our project like below:
```
root
├── build_server.yml
├── inventory
│   └── inventory.ini
├── roles
│   ├── common
│   │   └── tasks
│   │       └── main.yml
│   ├── httpd
│   │   ├── tasks
│   │   │   └── main.yml
│   │   └── handlers
│   │       └── main.yml
│   └── php
│       └── tasks
│           └── main.yml
└── vars
    └── common.yml
```
In our ```roles/common/httpd/tasks/main.yml``` we can add tasks only related to httpd.
We would structure our ```main.yml``` like below:
```yaml
- name: Install Webserver
  yum:
    name: "{{ webserver }}"
    state: present
  notify: Start Webserver

- name: Insert a welcome page
  copy:
    dest: "{{ welcome.dest }}"
    content: "{{ welcome.content }}"
```
We can then update the handler in ```roles/common/httpd/handlers/main.yml``` with:
```yaml
- name: Start Webserver
  service:
    name: "{{ webserver }}"
    state: started
```
We can then run the ansible playbook
```bash
ansible-playbook \                                                                                                                                13:03:26  ☁  master ☂ ➜ ⚡ ✚
    -i inventory/inventory.ini \
    build_server.yml
```
Our playbook will run each role and we can reuse those roles in other playbooks.
# Ansible Vault
Explain here how Ansible Vault can be used to keep our configuration secure
# Reset The Vagrant Boxes
After the tutorial we can reset the vagrant boxes back to their original state
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    reset.yml
```
Or destroy them with:
```bash
vagrant destroy
```