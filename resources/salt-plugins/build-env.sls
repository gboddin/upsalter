build-env-packages:
  pkg.installed:
    - pkgs:
      - git
      - wget
      - tar
{% if grains['os_family'] == 'RedHat' %}
      - rpm-build
      - rpmdevtools
      - make
{% elif grains['os_family'] == 'Debian' %}
      - build-essential
{% endif %}