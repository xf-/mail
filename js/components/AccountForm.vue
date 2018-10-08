<template>
  <div id="account-form">
    <form method="post" @submit.prevent.stop="onSubmit">
      <div class="hidden-visually">
        <!-- Hack for Safari and Chromium/Chrome which ignore autocomplete="off" -->
        <input id="fake_user" type="text" name="fake_user"
               autocomplete="off" tabindex="-1"
        >
        <input id="fake_password" type="password" name="fake_password"
               autocomplete="off" tabindex="-1"
        >
      </div>
      <fieldset>
        <div id="emptycontent" ref="emptyContent">
          <div class="icon-mail" />
          <h2>{{ t('mail', 'Connect your mail account') }}</h2>
        </div>
        <p class="grouptop">
          <input ref="accountName"
                 v-model="config.accountName"
                 type="text"
                 name="account-name"
                 :placeholder="t('mail', 'Name')"
                 autofocus
          >
        </p>
        <p class="groupmiddle">
          <input ref="mailAddress"
                 v-model="config.emailAddress"
                 type="email"
                 name="mail-address"
                 :placeholder="t('mail', 'Mail Address')"
                 required
          >
        </p>
        <p class="groupbottom">
          <input ref="mailPassword"
                 v-model="config.password"
                 type="password"
                 name="mail-password"
                 :placeholder="t('mail', 'Password')"
                 required
          >
        </p>

        <a class="toggle-manual-mode icon-caret-dark" @click.stop="toggleManualMode">{{ t('mail', 'Manual configuration') }}</a>

        <div ref="manualInputs" class="manual-inputs">
          <p class="grouptop">
            <input ref="imapHost"
                   v-model="config.imapHost"
                   type="text"
                   name="imap-host"
                   :placeholder="t('mail', 'IMAP Host')"
            >
          </p>
          <p id="setup-imap-ssl" class="groupmiddle">
            <select id="setup-imap-ssl-mode"
                    ref="imapSslMode"
                    v-model="config.imapSslMode"
                    name="imap-sslmode"
                    :title="t('mail', 'IMAP security')"
                    @change="onImapSslModeChange"
            >
              <option value="none">{{ t('mail', 'None') }}</option>
              <option value="ssl">{{ t('mail', 'SSL/TLS') }}</option>
              <option value="tls">{{ t('mail', 'STARTTLS') }}</option>
            </select>
          </p>
          <p class="groupmiddle">
            <input ref="imapPort"
                   v-model="config.imapPort"
                   type="number"
                   name="imap-port"
                   :placeholder="t('mail', 'IMAP Port')"
            >
          </p>
          <p class="groupmiddle">
            <input ref="imapUser"
                   v-model="config.imapUser"
                   type="text"
                   name="imap-user"
                   :placeholder="t('mail', 'IMAP User')"
            >
          </p>
          <p class="groupbottom">
            <input ref="imapPassword"
                   v-model="config.imapPassword"
                   type="password"
                   name="imap-password"
                   :placeholder="t('mail', 'IMAP Password')"
            >
          </p>
          <p class="grouptop">
            <input ref="smtpHost"
                   v-model="config.smtpHost"
                   type="text"
                   name="smtp-host"
                   :placeholder="t('mail', 'SMTP Host')"
            >
          </p>
          <p id="setup-smtp-ssl" class="groupmiddle">
            <select id="setup-smtp-ssl-mode"
                    ref="smtpSslMode"
                    v-model="config.smtpSslMode"
                    name="mail-smtp-sslmode"
                    :title="t('mail', 'SMTP security')"
                    @change="onSmtpSslModeChange"
            >
              <option value="none">{{ t('mail', 'None') }}</option>
              <option value="ssl">{{ t('mail', 'SSL/TLS') }}</option>
              <option value="tls">{{ t('mail', 'STARTTLS') }}</option>
            </select>
          </p>
          <p class="groupmiddle">
            <input ref="smtpPort"
                   v-model="config.smtpPort"
                   type="number"
                   name="smtp-port"
                   :placeholder="t('mail', 'SMTP Port')"
            >
          </p>
          <p class="groupmiddle">
            <input ref="smtpUser"
                   v-model="config.smtpUser"
                   type="text"
                   name="smtp-user"
                   :placeholder="t('mail', 'SMTP User')"
            >
          </p>
          <p class="groupbottom">
            <input ref="smtpPassword"
                   v-model="config.smtpPassword"
                   type="password"
                   name="smtp-password"
                   :placeholder="t('mail', 'SMTP Password')"
            >
          </p>
        </div>

        <input ref="submitButton"
               type="submit"
               class="primary"
               :value="t('mail', 'Connect')"
        >
      </fieldset>
    </form>
  </div>
</template>

<script>
export default {
	name: 'AccountForm',
	props: {
		settingsPage: Boolean,
	},
	data() {
		return {
			firstToggle: true,
			config: {
				accountName: $('#user-displayname').text() || '',
				emailAddress: $('#user-email').text() || '',
				password: '',
				autoDetect: true,
				imapHost: '',
				imapPort: 993,
				imapSslMode: 'ssl',
				imapUser: '',
				imapPassword: '',
				smtpHost: '',
				smtpPort: 587,
				smtpSslMode: 'tls',
				smtpUser: '',
				smtpPassword: '',
			},
		}
	},
	mounted() {
		if (this.settingsPage) {
			$(this.$refs.emptyContent).hide()
			$(this.$refs.submitButton).val(t('mail', 'Save'))
		}

		if (this.config.autoDetect) {
			$(this.$refs.mailPassword).show()
			$(this.$refs.manualInputs).hide()
		} else {
			$(this.$refs.mailPassword).hide()
		}
	},
	methods: {
		toggleManualMode: function() {
			this.config.autoDetect = !this.config.autoDetect

			$(this.$refs.manualInputs).slideToggle()
			this.$refs.imapHost.focus()

			if (!this.config.autoDetect) {
				if (this.firstToggle) {
					// Manual mode opened for the first time
					// -> copy email, password for imap&smtp
					this.config.imapUser = this.config.emailAddress
					this.config.imapPassword = this.config.password
					this.config.smtpUser = this.config.emailAddress
					this.config.smtpPassword = this.config.password
					this.firstToggle = false
				}

				$(this.$refs.mailPassword).slideToggle(() => {
					$(this.$refs.mailAddress)
						.parent()
						.removeClass('groupmiddle')
						.addClass('groupbottom')
					// Focus imap host input
					this.$refs.imapHost.focus()
				})
			} else {
				$(this.$refs.mailPassword).slideToggle()
				$(this.$refs.mailAddress)
					.parent()
					.removeClass('groupbottom')
					.addClass('groupmiddle')
			}
		},
		onImapSslModeChange: function() {
			const imapDefaultPort = 143
			const imapDefaultSecurePort = 993

			switch (this.config.imapSslMode) {
				case 'none':
				case 'tls':
					this.config.imapPort = imapDefaultPort
					break
				case 'ssl':
					this.config.imapPort = imapDefaultSecurePort
					break
			}
		},
		onSmtpSslModeChange: function() {
			const smtpDefaultPort = 587
			const smtpDefaultSecurePort = 465

			switch (this.config.smtpSslMode) {
				case 'none':
				case 'tls':
					this.config.smtpPort = smtpDefaultPort
					break
				case 'ssl':
					this.config.smtpPort = smtpDefaultSecurePort
					break
			}
		},
		onSubmit: function() {
			const emailAddress = this.config.emailAddress
			const accountName = this.config.accountName
			const password = this.config.password

			let config = {
				accountName,
				emailAddress,
				password,
				autoDetect: true,
			}

			// if manual setup is open, use manual values
			if (!this.config.autoDetect) {
				config = {
					accountName,
					emailAddress,
					password,
					imapHost: this.config.imapHost,
					imapPort: this.config.imapPort,
					imapSslMode: this.config.imapSslMode,
					imapUser: this.config.imapUser,
					imapPassword: this.config.imapPassword,
					smtpHost: this.config.smtpHost,
					smtpPort: this.config.smtpPort,
					smtpSslMode: this.config.smtpSslMode,
					smtpUser: this.config.smtpUser,
					smtpPassword: this.config.smtpPassword,
					autoDetect: false,
				}
			}
			// TODO: Handle form submit
			this.$store.dispatch('requestCreateAccount', config)
		},
	},
}
</script>
