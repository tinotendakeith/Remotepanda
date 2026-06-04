# Hillpaul Health Scan Clinic Birthday Notifier

System to automate birthday message sending using Twilio

### Installation

1. In the desired location `git clone` this repository
2. Run `composer install`
3. Copy `env` file to `.env` and set details according to your environment
4. After visit the url `http://your_install/install?secure=sure` to run the database migrations and seed the initial data
5. Set up a cron job to send birthday messages `wget http://your_install/notify`
6. Login using the credentials
    * Username : admin
    * Password : password