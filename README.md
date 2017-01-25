# Introduction to Ansible
The Ansible website describes it as:
> Ansible is a simple automation language that can perfectly describe an IT application infrastructure

Ansible runs on the client side so there is nothing to install on the systems being managed.
Ansible only needs SSH access to the machines that you are managing.
## Prerequisites
We will need to [install Ansible](http://docs.ansible.com/ansible/intro_installation.html) on your machine to use it.

### Installing Ansible on OSX
First install pip if you have not already installed it
```bash
sudo easy_install pip
```
Then install the latest version of Ansible
```bash
sudo pip install ansible
```
### Clone This Repository
If you want to try these examples for yourself, then clone the repository:
```bash
git clone https://github.com/thomaslorentsen/ansible-tutorial.git
cd ansible-tutorial
```
### Start Example Vagrant Boxes
To try the examples of this tutorial please start the vagrant boxes:
```bash
vagrant up
```
When we are done you can destroy the vagrant boxes with
```bash
vagrant destroy
```
# Demo Background
In this demo we are going to do what most developers do and build a website for our cat
# Inventory
An inventory describes all your servers in your infrastructure.
## Creating an inventory
An inventory is in the ```ini``` format which contains a heading to group a list of servers and then their host names:
```ini
[frontend]
frontend-server-1 ansible_host=192.168.0.32
frontend-server-2 ansible_host=192.168.0.33
frontend-server-3 ansible_host=192.168.0.34

[backend]
backend-server-1 ansible_host=192.168.33.34 ansible_user=vagrant 
backend-server-2 ansible_host=192.168.33.35 ansible_user=vagrant

[docker]
docker-1 ansible_host=192.168.33.36
```
# Playbooks
A playbook is Ansibles scripting language for orchestrating and configuring servers.
A playbook is in the ```yaml``` format making it easy to read and understand.
## Running A Playbook
We can install a webserver with a simple playbook.
```yaml
- hosts: frontend-1
  become: true
  tasks:
    - name: Install Web Server
      yum:
        name: httpd
        state: present
    - name: Start Web Server
      service:
        name: httpd
        state: started
```
You can run this with:
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    install_http_server.yml
```
When you run the playbook you will see that it runs all the tasks in the playbook.
After all the tasks have been completed it will print out a summary of the results.
```
PLAY [frontend-1] **************************************************************

TASK [setup] *******************************************************************
ok: [frontend-1]

TASK [Install Web Server] ***********************************************************
changed: [frontend-1]

TASK [Start Web Server] ********************************************************
changed: [frontend-1]

PLAY RECAP *********************************************************************
frontend-1                 : ok=3    changed=2    unreachable=0    failed=0
```
Now we can view the [Apache Welcome Page](http://192.168.33.31/) running on our web server.
## Sudo
To perform tasks that requires ```sudo``` you need to pass in the ```become``` option in the playbook.
```yaml
- hosts: frontend
  become: true
```
This will run all tasks with ```sudo```

You can run individual tasks with ```sudo``` as well by adding it to the task
```yaml
- hosts: frontend
  tasks:
    - name: Install Web Server
      become: true
      yum:
        name: httpd
      	state: present
```
You can also become a different user using the ```become_user``` option.
```yaml
- hosts: frontend
  tasks:
    - name: Create Website Directory
      become: true
      become_user: apache
      file:
        path: /var/www/website
      	state: directory
```
## Handlers


## Roles
Roles enable us to organise our playbooks.

We can now structure our project like below:
```
root
├── inventory
│   └── inventory.ini
├── roles
│   ├── httpd
│   │   └── tasks
│   │       └── main.yml
│   ├── php
│   │   └── tasks
│   │       └── main.yml
│   └── website
│       └── tasks
│           └── main.yml
└── build_website.yml
```
Now we can call each role from my playbook
```yaml
- hosts: frontend-1
  become: true
  roles:
    - httpd
    - php
    - website
```
When we run the playbook, the tasks in each role is run
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    build_website.yml
```
We can now easily reuse these roles.
## Running On Multiple Hosts
Pearl expects her website to be in very high demand.
So she would like her website to be installed on three webservers.
```yaml
- hosts: frontend
  become: true
  roles:
    - httpd
    - php
    - website
```
A load balancer will be set up on a forth server.
```yaml
- name: Install HA Proxy
  yum:
    name: haproxy
    state: present

- name: Install HA Proxy Configuration
  template:
    src: haproxy.cfg
    dest: /etc/haproxy/haproxy.cfg

- name: Start HA Proxy
  service:
    name: haproxy
    state: started
```
We can now configure three webservers and a load balancer:
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    load_balanced_website.yml
```
Now [Pearls Website](http://192.168.33.34/) from our load balancer.
There is no chance of her website falling over when there is high demand!
# Docker
To demonstrate how Ansible will help us with micro services we will install a Redis running in docker.


```yaml
- name: Create Redis Container
  docker_container:
    name: redis
    image: redis
    command: redis-server --appendonly yes
    state: present
    exposed_ports:
      - 6379
    volumes:
      - /redisdata
```
We can use Ansible Galaxy to help us be DRY
```bash
ansible-galaxy install geerlingguy.repo-epel
```
When the playbook is run docker will be installed
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    docker.yml
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
