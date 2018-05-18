<script>
import { authMethods } from '@state/helpers'
import appConfig from '@src/app.config'

export default {
  page: {
    title: 'Log in',
    meta: [{ name: 'description', content: `Log in to ${appConfig.title}` }],
  },
  components: {},
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
            this.$router.push({ name: 'home' })
        })
        .catch(error => {
            this.tryingToLogIn = false
            this.authError = error
        })
    },
  },
}
</script>

<template>
    <div class="flex-container text-center">
        <form @submit.prevent="tryToLogIn" class="form-signin">
            <!--<img class="mb-4" src="https://getbootstrap.com/assets/brand/bootstrap-solid.svg" alt="" width="72" height="72">-->
            <h1>WhiteBikes 2</h1>
            <h2 class="h3 mb-3 font-weight-normal">Please sign in</h2>

            <label for="inputPhone" class="sr-only">Phone number</label>
            <input v-model="username" type="tel" id="inputPhone" class="form-control" placeholder="Phone number" required autofocus>

            <label for="inputPassword" class="sr-only">Password</label>
            <input v-model="password" type="password" id="inputPassword" class="form-control" placeholder="Password" required>

            <button class="btn btn-lg btn-primary btn-block" type="submit">
                <BaseIcon
                        v-if="tryingToLogIn"
                        name="sync"
                        spin />

                <span v-else>Sign in</span>
            </button>

            <p v-if="authError">
                There was an error logging in to your account.
            </p>

            <p class="mt-5 mb-3 text-muted">&copy; 2017-2018</p>
        </form>
    </div>
</template>

<style lang="scss" scoped>
@import 'frontend/design/index.scss';

.flex-container {
    display: flex;
    flex-direction: column;
    height: 100vh; /*new*/
    background-color: #f5f5f5;
}

.form-signin {
    width: 100%;
    max-width: 330px;
    padding: 15px;
    margin: auto;
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
