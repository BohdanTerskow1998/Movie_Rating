<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-vue/dist/bootstrap-vue.css';
import './Styles/main.css';

const loader = ref(null);

const form = {
    country: null
};

const movies = ref(null);

const select = (e) => {
    form.country = e.target.value
}

const submit = async (e) => {
    e.preventDefault();

    const country = form.country;
    if (country !== null) {
        movies.value = null;
        loader.value = true;
        document.querySelector(".btn-success").disabled = true;

        const headers = {
            "accept": "application/json"
        };

        const formData = new FormData();
        formData.append("country", country);

        try {
            const result = await axios.post(route('getTop'), formData, { headers });
            const response = result.data;

            if (response.status) {
                const content = response.message;
                const getMovies = content.results;

                const ratingPromises = getMovies.map(async (e) => {
                    try {
                        var request = await axios.get(`/api/rating/${e.title}`);
                        e.favourites = await request.data.favourites;
                    } catch (error) {
                        e.favourites = 0;
                    }
                });

                await Promise.all(ratingPromises);

                movies.value = content.results;
                loader.value = null;
                document.querySelector(".btn-success").disabled = false;
            } else {
                alert("Something went wrong! Try it later!")
            }
        } catch (error) {
            alert("Something went wrong! Try it later!")
        }
    } else {
        alert("Please, choose a country!")
    }
}

const like = (title, param) => {
    var titleForStorage = title.replace(/[^a-zA-Z\s]/g, '');
    if(!localStorage.getItem(titleForStorage)) {
        const headers = {
            "Content-Type": "application/json"
        };

        const formData = new FormData();
        formData.append("title", title);
        param === "+" ? formData.append("point", 1) : formData.append("point", -1);

        axios.post(route('rating'), formData, { headers })
            .then(result => {
                const response = result.data;
                if(response.status) {
                    localStorage.setItem(titleForStorage, true);
                    const span = document.querySelector(`span[title="${title}"]`);
                    if(param === "+") {
                        span.innerText = parseInt(span.innerText) + 1
                    } else {
                        if(parseInt(span.innerText) > 0) {
                            span.innerText = parseInt(span.innerText) - 1;
                        } else {
                            localStorage.removeItem(titleForStorage, true);
                        }
                    }
                } else {
                    alert("Something went wrong! Try it later!");
                }
            })
            .catch(error => {
                console.error(error);
                alert("Something went wrong! Try it later!");
            });
    }
}

</script>

<template>
    <Head title="TopMovie" />

    <div class="main">

        <div class="select">
            <select class="form-select" aria-label="Default select example" @change="select">
              <option selected disabled value="">Open this select menu</option>
              <option value="UA">Ukraine</option>
              <option value="DE">Germany</option>
              <option value="PL">Poland</option>
            </select>
        </div>

        <div class="button">
            <button class="btn btn-success" @click="submit">Submit</button>
        </div>

    </div>

    <hr />

    <div class="loader" v-if="loader !== null">
        <div class="spinner"></div>
    </div>

    <div class="top" v-if="movies !== null">
        <ul v-for="movie in movies" :key="movie.id">
            <li>
                {{movie.title}}
                <button class="btn btn-outline-dark ml-2" @click="like(movie.title, '+')">+</button>
                <span class="ml-2" :title="movie.title">{{movie.favourites}}</span>
                <button class="btn btn-outline-dark ml-2" @click="like(movie.title, '-')">-</button>
            </li>
        </ul>
    </div>
</template>
