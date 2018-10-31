<template>
	<div v-if="state === STATES.EDITING"
		 class="message-composer">
		<select class="mail-account"
				v-model="selectedAlias"
				v-on:keyup="onInputChanged">
			<option v-for="alias in aliases" :value="alias.id">
				{{ t('mail', 'from') }} {{alias.name}} &lt;{{alias.emailAddress}}&gt;
			</option>
		</select>
		<div class="composer-fields">
			<a href="#"
			   class="composer-cc-bcc-toggle transparency"
			   :class="{ hidden: hasCC }">
				{{ t ('mail', '+ cc/bcc') }}
			</a>
			<label class="to-label transparency" for="to">
				{{ t('mail', 'to') }}
			</label>
			<Multiselect :options="toVal"
						 id="to"
					     v-on:keyup="onInputChanged" />
		</div>
		<div class="composer-fields"
			 v-if="hasCC">
			<label for="cc" class="cc-label transparency">
				{{ t('mail', 'cc') }}
			</label>
			<Multiselect :options="ccVal"
						 id="cc"
						 v-on:keyup="onInputChanged" />
		</div>
		<div class="composer-fields"
			 v-if="hasCC">
			<label for="bcc" class="bcc-label transparency">
				{{ t('mail', 'bcc') }}
			</label>
			<Multiselect :options="bccVal"
						 id="bcc"
						 v-on:keyup="onInputChanged" />
		</div>
		<div class="composer-fields">
			<label for="subject" class="subject-label transparency">
				{{ t('mail', 'Subject') }}
			</label>
			<input type="text"
				   id="subject"
				   name="subject"
				   v-model="subjectVal"
				   v-on:keyup="onInputChanged"
				   class="subject" autocomplete="off"
				   :placeholder="t('mail', 'Subject')"/>
		</div>
		<div class="composer-fields">
			<div v-if="noReply"
				 class="warning noreply-box">
				{{ t('mail', 'Note that the mail came from a noreply address so	your reply will probably not be read.') }}
			</div>
			<textarea name="body"
					  class="message-body"
					  v-autosize
					  v-model="bodyVal"
					  v-on:keyup="onInputChanged"
					  :placeholder="t('mail', 'Message …')">{{message}}</textarea>
		</div>
		<div class="submit-message-wrapper">
			<input class="submit-message send primary"
				   type="submit"
				   :value="submitButtonTitle"
				   v-on:click="onSend">
		</div>
		<ComposerAttachments v-model="attachments" />
		<span id="draft-status" v-if="savingDraft === true">{{ t('mail', 'Saving draft …') }}</span>
		<span id="draft-status" v-else-if="savingDraft === false">{{ t('mail', 'Draft saved') }}</span>
	</div>
	<Loading v-else-if="state === STATES.SENDING"
			 :hint="t('mail', 'Sending …')" />
	<div v-else-if="state === STATES.ERROR"
		 class="emptycontent">
		<h2>{{ t('mail', 'Error sending your message') }}</h2>
		<p v-if="errorText">{{ errorText }}</p>
		<button v-on:click="state = STATES.EDITING"
				class="button">{{ t('mail', 'Go back') }}</button>
		<button v-on:click="onSend"
		        class="button primary">{{ t('mail', 'Retry') }}</button>
	</div>
	<div v-else
		 class="emptycontent">
		<h2 v-if="!isReply">{{ t('mail', 'Message sent!') }}</h2>
		<h2 v-else>{{ t('mail', 'Reply sent!') }}</h2>
		<button v-on:click="reset"
				v-if="!isReply"
				class="button primary">{{ t('mail', 'Write another message') }}</button>
	</div>
</template>

<script>
	import _ from 'lodash'
	import Autosize from 'vue-autosize'
	import {Multiselect} from 'nextcloud-vue'
	import Vue from 'vue'

	import Loading from './Loading'
	import ComposerAttachments from "./ComposerAttachments";

	Vue.use(Autosize)

	const STATES = Object.seal({
		EDITING: 0,
		SENDING: 1,
		ERROR: 2,
		FINISHED: 3,
	})

	export default {
		name: 'Composer',
		components: {
			ComposerAttachments,
			Loading,
			Multiselect,
		},
		props: {
			replyTo: {
				type: Object,
			},
			to: {
				type: Array,
				default: () => [],
			},
			cc: {
				type: Array,
				default: () => [],
			},
			subject: {
				type: String,
				default: '',
			},
			draft: {
				type: Function,
				required: true,
			},
			send: {
				type: Function,
				required: true,
			}
		},
		data () {
			return {
				hasCC: true,
				selectedAlias: this.$route.params.accountId,
				toVal: this.to,
				ccVal: this.cc,
				bccVal: [],
				subjectVal: this.subject,
				bodyVal: '',
				attachments: [],
				noReply: false,
				message: '',
				submitButtonTitle: t('mail', 'Send'),
				draftsPromise: Promise.resolve(),
				savingDraft: undefined,
				saveDraftDebounced: _.debounce(this.saveDraft, 700),
				state: STATES.EDITING,
				errorText: undefined,
				STATES
			}
		},
		computed: {
			aliases () {
				return this.$store.getters.getAccounts()
			},
			isReply () {
				return !_.isUndefined(this.replyTo)
			}
		},
		filters: {

		},
		methods: {
			getMessageData () {
				return uid => {
					return {
						account: this.selectedAlias,
						to: this.toVal,
						cc: this.ccVal,
						bcc: this.bccVal,
						draftUID: uid,
						subject: this.subjectVal,
						body: this.bodyVal,
						attachments: this.attachments,
						folderId: this.replyTo ? this.replyTo.folderId : undefined,
						messageId: this.replyTo ? this.replyTo.messageId : undefined,
					}
				}
			},
			saveDraft (data) {
				this.savingDraft = true
				this.draftsPromise = this.draftsPromise
					.then(uid => this.draft(data(uid)))
					.catch(console.error.bind(this))
					.then(uid => {
						this.savingDraft = false
						return uid
					})
			},
			onInputChanged () {
				this.saveDraftDebounced(this.getMessageData())
			},
			onSend () {
				this.state = STATES.SENDING

				return this.draftsPromise
					.then(this.getMessageData())
					.then(data => this.send(data))
					.then(() => console.info('message sent'))
					.then(() => this.state = STATES.FINISHED)
					.catch(e => {
						console.error('could not send message', e)
						if (e && e.toString) {
							this.errorText = e.toString()
						}
						this.state = STATES.ERROR
					})
			},
			reset () {
				this.toVal = ''
				this.ccVal = ''
				this.bccVal = ''
				this.subjectVal = ''
				this.bodyVal = ''
				this.attachments = []
				this.errorText = undefined
				this.state = STATES.EDITING
			}
		}
	}
</script>

<style scoped>
	.message-composer {
		margin: 0;
		margin-bottom: 10px; /* line up with the send button */
		z-index: 100;
	}

	#reply-composer .message-composer {
		margin: 0;
	}

	.composer-fields {
		display: flex;
		border-top: 1px solid #eee;
	}
	.composer-fields .multiselect,
	.composer-fields input,
	.composer-fields textarea {
		flex-grow: 1;
		max-width: none;
		border-left: none;
		border-right: none;
		border-radius: 0px;
	}

	#to,
	#cc,
	#bcc {
		border: none;
	}

	#to,
	#cc,
	#bcc,
	input.subject,
	textarea.message-body {
		padding: 12px;
		padding-left: 64px;
		margin: 0;
	}

	#to {
		padding-right: 60px; /* for cc-bcc-toggle */
	}

	input.cc,
	input.bcc,
	input.subject,
	textarea.message-body {
		border-top: none;
	}

	input.subject {
		font-size: 20px;
		font-weight: 300;
	}

	input.subject,
	textarea.message-body {
		padding-left: 30px;
	}

	textarea.message-body {
		min-height: 300px;
		resize: none;
		padding-right: 25%;
	}

	#draft-status {
		padding: 5px;
		opacity: 0.5;
		font-size: small;
	}

	label.to-label,
	label.cc-label,
	label.bcc-label,
	label.subject-label {
		padding: 12px;
		padding-left: 30px;
		cursor: text;
		opacity: .5;
	}

	label.bcc-label {
		top: initial;
		bottom: 0;
	}

	.composer-cc-bcc-toggle {
		position: absolute;
		right: 0;
		padding: 12px;
	}

	textarea.reply {
		min-height: 100px;
	}

	input.submit-message,
	.submit-message-wrapper {
		position: fixed;
		bottom: 10px;
		right: 15px;
	}

	.submit-message-wrapper {
		position: fixed;
		height: 36px;
		width: 60px;
	}

	.submit-message.send {
		padding: 12px;
	}

</style>

<style>
	.multiselect .multiselect__tags {
		border: none;
	}
</style>
