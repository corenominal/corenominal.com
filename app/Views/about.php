<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

    <h1 class="mb-4">About</h1>

    <p>
        <button type="button" class="border-0 bg-transparent p-0 text-body text-decoration-underline"
                data-bs-toggle="popover" data-bs-trigger="focus" data-bs-placement="bottom"
                data-bs-title="Agnostic"
                data-bs-content="Someone who believes the existence of god or ultimate truth is unknown - neither affirmed nor denied.">Agnostic</button>
        |
        <button type="button" class="border-0 bg-transparent p-0 text-body text-decoration-underline"
                data-bs-toggle="popover" data-bs-trigger="focus" data-bs-placement="bottom"
                data-bs-title="Humanist"
                data-bs-content="Someone who prioritises human welfare, reason, and ethics, believing people can lead meaningful and moral lives without religious doctrine.">Humanist</button>
        |
        <button type="button" class="border-0 bg-transparent p-0 text-body text-decoration-underline"
                data-bs-toggle="popover" data-bs-trigger="focus" data-bs-placement="bottom"
                data-bs-title="Allyship Matters"
                data-bs-content="A commitment to actively supporting and advocating for marginalised or underrepresented communities - solidarity across differences makes society better for everyone.">Allyship Matters</button>
    </p>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => new bootstrap.Popover(el));
    });
    </script>

    <p>My name is Philip Newborough and I'm a <?= $age = ((int)date('Y') - 1975) ?> year old tech lover and web developer who spends a lot of time crafting things for the web. When I'm not glued to my desk, you can find me lost in the grimdark future of Warhammer, tackling proud husband/dad/grumpa duties, listening to tech podcasts, or <a href="/rides">cycling around on my bike</a>.</p>

    <p>I've been working in web development since the late 1990s, and have been a professional web developer since 2000. I have a passion for building things that are fast, accessible, and easy to use. I love to learn new things, and I'm always looking for ways to improve my skills.</p>

    <figure class="mb-3">
        <img class="img-fluid rounded p-2 border" src="https://corenominal.com/uploads/blog/media/2b9d5f8b-ed69-42d8-86fa-d58346af8326.jpg" alt="Enjoying a cycling break in the New Forest, Hampshire, UK.">
        <figcaption class="text-secondary small mt-1">Photo by Becky Newborough.</figcaption>
    </figure>

    <p>My first computer was a Commodore 64, and I've been fascinated by technology ever since. I studied mechanical engineering in college and have always been interested in how things work. I purchased my first PC in the early 1990s, and have been coding ever since.</p>

    <p>I currently work full-time as a web developer for a large construction company where I build and maintain their internal web applications.</p>

    <h2 class="mt-5">About This Site</h2>

    <p>This site is a personal project of mine, and is a place where I can share my thoughts, ideas, and projects with the world. It's also a place where I can experiment with new technologies and techniques, and learn from my mistakes.</p>

    <p>I've rebuilt this site several times over the years. The current version is built with CodeIgniter, a powerful PHP framework that helps me create robust and scalable web applications. I use it to manage the content and functionality of this site, ensuring it performs well under typical usage scenarios.</p>

    <p>The site is hosted on an <a href="https://www.ovhcloud.com/" target="_blank" rel="noopener noreferrer">OVH VPS</a> running <a href="https://www.debian.org/" target="_blank" rel="noopener noreferrer">Debian</a>, with <a href="https://httpd.apache.org/" target="_blank" rel="noopener noreferrer">Apache</a> as the web server and <a href="https://mariadb.org/" target="_blank" rel="noopener noreferrer">MariaDB</a> as the database. I use Git for version control and all the code for this site is stored in a <a href="<?= config('Urls')->git_repository ?>" target="_blank" rel="noopener noreferrer">public Git repository</a>.</p>

<?= $this->endSection() ?>