Vagrant.configure("2") do |config|
  config.vm.box = "wheezy64"
    config.vm.box_url = "http://puppet-vagrant-boxes.puppetlabs.com/debian-70rc1-x64-vbox4210.box"
    config.vm.provision :puppet do |puppet|
    puppet.manifests_path = "build/puppet/manifests"
  end
  config.vm.network :forwarded_port, host: 2345, guest: 80
  config.vm.provider "virtualbox" do |v|
    v.gui = false
  end
end
