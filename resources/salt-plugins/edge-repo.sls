{% set config = salt['grains.filter_by']({
    'Debian': {
        'repo': False
    },
    'RedHat': {
        'repo': 'http://repo.siwhine.net/el/'
    },
}) %}

{% if config.repo %}

edge-repo:
  # repository :
  pkgrepo.managed:
    - humanname: CentOS-$releasever - Edge repo
    - baseurl: http://repo.siwhine.net/{{ grains['osfinger'].tolower() }}
    - gpgcheck: 1
    - gpgkey: http://repo.siwhine.net/EDGE-REPO-KEY.pub

git-installed:
  pkg.installed:
    - name: git

{% endif %}