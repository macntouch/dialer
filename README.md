# dialer
Dialer Stasis Demo

# Use

* run install.sh to load php dependencies using composer
* make sure ari.conf and http.conf are configured similar to examples
* asterisk must be configured with outbound dialing capable trunk
* start asterisk
* start recorder stasis app (# php recorder.php)
* edit dialer code to change trunk config and target phone number
* run dialer
* when call is answered, prompt to record message should be heard
* when hangup, recording stops (# key not implemented yet).

Note that dialer code selects a unique id for channel, which carries
through to the recorded file, which will be found in /var/spool/asterisk/recording.

