#!/bin/bash
set -e

echo "=== Setting up GitHub Actions Runner in WSL Ubuntu ==="

# Create runner user
if ! id runner &>/dev/null; then
  useradd -m -s /bin/bash runner
  echo "runner ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/runner
  chmod 440 /etc/sudoers.d/runner
  echo "Created runner user"
fi

# Install packages
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq software-properties-common > /dev/null 2>&1
add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
apt-get update -qq
apt-get install -y -qq \
  php8.4 php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-sqlite3 php8.4-mysql php8.4-gd php8.4-intl \
  php8.4-bcmath php8.4-zip php8.4-fileinfo \
  unzip curl git zip jq > /dev/null 2>&1

# Install Node.js 22
if ! command -v node &>/dev/null; then
  curl -fsSL https://deb.nodesource.com/setup_22.x | bash - > /dev/null 2>&1
  apt-get install -y -qq nodejs > /dev/null 2>&1
fi

# Install Composer
if ! command -v composer &>/dev/null; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1
fi

echo "=== Installed versions ==="
php -v | head -1
node -v
npm -v
composer -V | head -1

# Download GitHub Actions runner
RUNNER_DIR=/home/runner/actions-runner
mkdir -p "$RUNNER_DIR"
cd "$RUNNER_DIR"

echo "=== Downloading runner ==="
RUNNER_VER="2.324.0"
RUNNER_URL="https://github.com/actions/runner/releases/download/v${RUNNER_VER}/actions-runner-linux-x64-${RUNNER_VER}.tar.gz"
curl -sL "$RUNNER_URL" | tar xz

chown -R runner:runner "$RUNNER_DIR"

echo "=== Runner files ==="
ls -la config.sh run.sh

echo ""
echo "=== SETUP COMPLETE ==="
echo "Next: register the runner with GitHub"
echo "  sudo -u runner bash -c 'cd /home/runner/actions-runner && ./config.sh --url https://github.com/nreljed/laravel.shop --token <TOKEN> --labels self-hosted,linux,wsl --name wsl-runner --replace'"
echo ""
echo "Then run: sudo -u runner bash -c 'cd /home/runner/actions-runner && ./run.sh'"
