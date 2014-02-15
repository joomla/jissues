Vagrant.configure("2") do |config|
  config.vm.box = "debian-7-64-elkuku-4"
  config.vm.box_url = "https://dl.dropboxusercontent.com/s/nc9fdcl4mtgsve3/debian-7-64-elkuku-4.box"
  config.vm.provision :puppet do |puppet|
    puppet.manifests_path = "build/puppet/manifests"
  end
  config.vm.network :forwarded_port, host: 2345, guest: 80
  config.vm.provider "virtualbox" do |v|
    v.gui = false
  end
end
