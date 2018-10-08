<template>
  <div id="content" class="mail">
    <div id="app-content">
      <div class="container">
        <div id="setup-content container">
          <Loading
            v-if="loading"
            :hint="t('mail', 'Setting up your account')"
          />
          <account-form v-else :settings-page="false" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AccountForm from '../components/AccountForm'
import Loading from '../components/Loading'

export default {
	name: 'Setup',
	components: {
		AccountForm,
		Loading,
	},
	computed: {
		loading() {
			return this.$store.state.loading
		},
	},
}
</script>
