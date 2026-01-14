
Let's get that button and card setup for your 1989shack.com domain.

Since you want a vanilla implementation or something in a framework, I'll provide a simple example using HTML, CSS, and JavaScript. We can use this as a starting point.

Here's a basic card with an image and a button:
```
<div class="card">
  <img src="image.jpg" alt="Card Image">
  <h2>Card Title</h2>
  <p>Card description.</p>
  <button class="btn">Take Action</button>
</div>
```
```
.card {
  width: 300px;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.card img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 10px 10px 0 0;
}

.btn {
  background-color: #4CAF50;
  color: #fff;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.btn:hover {
  background-color: #3e8e41;
}
```
```
// Add event listener to the button
document.querySelector('.btn').addEventListener('click', () => {
  // Trigger something here, e.g., modal, navigation, etc.
  console.log('Button clicked!');
});
```
This is a basic example. We can customize it according to your needs.

For integrating with GitHub, you might want to look into using the GitHub API or a library like Octokit. However, without more information on what you want to achieve, it's hard to provide a more specific solution.

If you have a particular design in mind or are facing issues with existing code, feel free to share more details, and I'll do my best to assist you.
