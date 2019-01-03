{{-- define servers --}}
@servers(['production' => ['user@domain'], 'staging' => ['user@domain']])

{{-- setup vars --}}
@setup
    $repository = ''; {{-- git repository --}}
    $releases_path = ''; {{-- releases path --}}
    $app_path = ''; {{-- app path --}}
    $release = date('YmdHis');
    $new_release_dir = $releases_path .'/'. $release;
@endsetup

{{-- deploy process --}}
@story('deploy')
    clone
    composer
    symlinks
    migrate
@endstory

{{-- clone git repository --}}
@task('clone_repository', ['on' => $on])
    echo 'Cloning repository'
    [ -d {{ $releases_path }} ] || mkdir {{ $releases_path }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
@endtask

{{-- install composer dependencies --}}
@task('composer', ['on' => $on])
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install --prefer-dist --no-dev -q -o
@endtask

{{-- update app symlinks --}}
@task('symlinks', ['on' => $on])
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_path }}/storage {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_path }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_path }}/current
@endtask

{{-- migrate app database --}}
@task('migrate', ['on' => $on])
    php {{ $app_path }}/current/artisan migrate --force
@endtask
