Vagrant.configure("2") do |config|
    config.vm.hostname = "cruithne"
    config.ssh.insert_key = false
    config.ssh.forward_agent = true

    config.ssh.shell="bash"
    config.vm.box = "ubuntu/xenial64"
    config.vm.box_url = "https://cloud-images.ubuntu.com/xenial/current/xenial-server-cloudimg-amd64-vagrant.box"
    config.vm.network :forwarded_port, guest: 80, host: 3400
    config.vm.network :forwarded_port, guest: 443, host: 3500
    config.vm.network "private_network", ip: "192.168.88.99"
    #config.vm.synced_folder "", "/var/www/cruithne", owner:"vagrant", group:"vagrant", :mount_options => ["dmode=777","fmode=666"]
    config.vm.synced_folder "", "/var/www/cruithne", :nfs => { :mount_options => ["dmode=777","fmode=666"] }
    if Vagrant.has_plugin?('vagrant-hostsupdater')
        config.hostsupdater.aliases = ['store.cruithne.test']
    end
    config.vm.provision "shell", path: "environment/scripts/provisioner.sh"

    # VirtualBox.
    config.vm.provider :virtualbox do |v|
      v.linked_clone = false
      v.name = "cruithne"
      v.memory = 3072
      v.cpus = 2
      v.customize ['modifyvm', :id, '--natdnshostresolver1', 'on']
      v.customize ['modifyvm', :id, '--ioapic', 'on']
      v.gui = false
    end
end
