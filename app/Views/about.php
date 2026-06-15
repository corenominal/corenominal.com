<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

    <h1 class="mb-4">About</h1>

    <p>I'm a <?= $age = ((int)date('Y') - 1975) ?> year old tech lover and web developer who spends a lot of time crafting things for the web. When I'm not glued to my desk, you can find me lost in the grimdark future of Warhammer, tackling proud Husband/Dad/Grumpa duties, listening to tech podcasts, or cycling around on my bike.</p>

    <p>I've been working in web development since the late 1990s, and have been a professional web developer since 2000. I have a passion for building things that are fast, accessible, and easy to use. I love to learn new things, and I'm always looking for ways to improve my skills.</p>

    <figure class="mb-3">
        <img class="img-fluid rounded p-2 border" src="https://corenominal.com/uploads/blog/media/88153c8a-c977-4212-a03a-97680df54e6c.jpg" alt="A vintage 1984 Christmas morning photograph of a young me smiling proudly next to a large, boxed Star Wars 'Return of the Jedi' Millennium Falcon toy">
        <figcaption class="text-secondary small mt-1">A young me in 1984 with my Star Wars Millennium Falcon toy.</figcaption>
    </figure>

    <p>My first computer was a Commodore 64, and I've been fascinated by technology ever since. I studied mechanical engineering in college and have always been interested in how things work. I purchased my first PC in the early 1990s, and have been coding ever since.</p>

    <p>I currently work full-time as a web developer for a large construction company where I build and maintain their internal web applications.</p>

    <h2>About This Site</h2>

    <p>This site is built with CodeIgniter, a powerful PHP framework that helps me create robust and scalable web applications. I use it to manage the content and functionality of this site, ensuring it performs well under typical usage scenarios.</p>

    <p>The site is hosted on an <a href="https://www.ovhcloud.com/" target="_blank" rel="noopener noreferrer">OVH VPS</a> running <a href="https://www.debian.org/" target="_blank" rel="noopener noreferrer">Debian</a>, with <a href="https://httpd.apache.org/" target="_blank" rel="noopener noreferrer">Apache</a> as the web server and <a href="https://mariadb.org/" target="_blank" rel="noopener noreferrer">MariaDB</a> as the database. I use Git for version control and all the code for this site is stored in a <a href="<?= Config('urls')->gitRepository ?>" target="_blank" rel="noopener noreferrer">public Git repository</a>.</p>

<?= $this->endSection() ?>