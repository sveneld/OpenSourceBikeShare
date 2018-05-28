<script>
    import appConfig from '@src/app.config'
    import Layout from '@layouts/main'
    import { authMethods } from '@state/helpers'
    import GoogleMap from "../../components/google-map.vue";

    export default {
        page: {
            title: 'Log in',
            meta: [{ name: 'description', content: `Log in to ${appConfig.title}` }],
        },
        data() {
            return {
                username: '',
                password: '',
                authError: null,
                tryingToLogIn: false,
            }
        },
        methods: {
            ...authMethods,
            // Try to log the user in with the username
            // and password they provided.
            tryToLogIn() {
                this.tryingToLogIn = true
                // Reset the authError if it existed.
                this.authError = null
                return this.logIn({
                    username: this.username,
                    password: this.password,
                })
                    .then(token => {
                        this.tryingToLogIn = false
                        this.$router.push({name: 'home'})
                    })
                    .catch(error => {
                        this.tryingToLogIn = false
                        this.authError = error
                    })
            },
        }
    }
</script>

<template>

    <div class="flex-container text-center">
        <form @submit.prevent="tryToLogIn" class="form-signin">
            <h1><span class="md-display-1">WhiteBikes 2</span></h1>
            <span class="md-headline">Please sign in</span>

            <md-field>
                <label>Phone number</label>
                <md-input v-model="username"></md-input>
            </md-field>
            <md-field :md-toggle-password="false">
                <label>Password</label>
                <md-input v-model="password" type="password"></md-input>
            </md-field>

            <md-button type="submit" style="width:100%; margin:0" class="md-raised md-primary">
                <BaseIcon
                        v-if="tryingToLogIn"
                        name="sync"
                        spin />

                <span v-else>Sign in</span>
            </md-button>

            <p v-if="authError">
                There was an error logging in to your account.
            </p>

            <p>&copy; 2017-2018</p>
        </form>
    </div>

</template>

<style lang="scss" scoped>
    @import 'frontend/design/index.scss';

    .flex-container {
        display: flex;
        text-align: center;
        flex-direction: column;
        height: 100vh; /*new*/
        /*background-color: #f5f5f5;*/
    }

    .form-signin {
        width: 100%;
        max-width: 330px;
        padding: 15px;
        margin: auto;
        background-color: #fff;
    }
    .form-signin .checkbox {
        font-weight: 400;
    }
    .form-signin .form-control {
        position: relative;
        box-sizing: border-box;
        height: auto;
        padding: 10px;
        font-size: 16px;
    }
    .form-signin .form-control:focus {
        z-index: 2;
    }
    .form-signin input[type="email"] {
        margin-bottom: -1px;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }
    .form-signin input[type="password"] {
        margin-bottom: 10px;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
</style>