![image](https://github.com/ahdghazal/farmly/assets/111571170/f5845639-24b9-4cbf-bbf3-d80399806b74)

# Farmly App

## Abstract

Farmly is a mobile application designed to mainly solve the problem of the lack of experience in farming and inability to utilize available house gardening spaces, and the increasing prices of organic goods by encouraging people to plant their veggies, fruits, and herbs instead of buying them from the market. It supports and encourages home farming, and with its interactive interface and features, it offers a comprehensive database of information to assist users at every stage of their planting and gardening journey. The platform offers information about crops with planting guidelines, care instructions, and growth stages for various species including flowers, and herbs, all of that mapped to the user’s specific conditions like soil, weather, and location. Users can utilize their gardening space to successfully grow plants for personal use or to start a business, by selling plants and related products. In addition to the informative plant database, users can securely access the app for an overview of their garden's status. Real-time weather integration will be included to help users plan gardening activities effectively, plus the pest’s identification feature to ensure plant health. Community engagement is enabled through a forum where users can share knowledge and experiences. Farmly will also have a tool website for administrators to control everything on the platform, like user accounts, database information, and community involvement. This tool will be crucial for keeping Farmly running smoothly and helping it grow. Through Farmly, we aim to promote sustainable agriculture, spread environmental consciousness, and build a vibrant community of home gardeners dedicated to nurturing green spaces and enhancing food and goods resources at the grassroots level.

## Choosing the Architecture

### Backend Architecture

In the backend development of our software project, we have implemented the Model-View-Controller (MVC) architecture. This approach has helped us create a well-organized and modular structure for our backend codebase. The model component is responsible for encapsulating the data and business logic, ensuring proper data handling and manipulation.


## Programming Languages, Frameworks, and Other Services

Laravel was our choice for backend development because it's a strong framework that comes with built-in tools for handling web and mobile applications' features for routing, authentication, and database management. Laravel's clear syntax and extensive documentation make development faster and improve the quality of the work. Plus, there's a large community of Laravel users who share resources and offer support.

We used AWS to deploy our project to a live server, EC2 for servers and RDS for databases. EC2 offers resizable compute capacity in the cloud, allowing easy scaling of applications based on demand. RDS provides easy database management by handling routine tasks like backups and scaling.

To manage our project's database, MySQL was the best option because it's open-source, reliable, widely used, and well-documented. MySQL offers high performance due to the way it's structured and works with many frameworks, making it a good choice for handling data and complex queries in applications. Additionally, Postman is employed for API development and testing, allowing seamless communication with the MySQL database. This combination of MySQL and Postman enhanced the efficiency and maintainability of our database management system.

For our real-time features like notifications and reminders, Firebase was our choice because it offers services including authentication, real-time communication, cloud storage, and notifications. It simplifies the development process with available SDKs, making it ideal for our mobile application.

The design and structure of a database system based on the provided schema. The schema consists of several tables and data types representing various entities and their relationships. The proposed database will efficiently store and manage data related to users, plants, gardens, posts, replies, likes, announcements, conversations, reminders, notifications, and products.

![image](https://github.com/ahdghazal/farmly/assets/111571170/9af7298c-8f1b-4184-82f4-5aeebb6b4a1f)

## Features

- **User Authentication and Authorization:** Users can register and log in to the application securely. Authentication is handled securely using Laravel’s built-in authentication system, and verifying users by sending OTP to their emails using SMTP.
- **User and Admin Profile:** This allows all the users and administrators to manage their profiles’ personal data, and privacy and security settings.
- **Homepage:** This provides an overall summary for the users whenever they open the applications to check their data, weather, new messages, notifications and announcements, and their garden plants’ needs and reminders.
- **Personal Garden Management System:** Users can create and manage their gardens within the app, they can add, update, and remove both gardens and plants from their gardens, as well as monitor plant health and growth using the data from the collected dataset.
- **Plant Dataset:** The application includes an extensive plant database with information on various plant species. Plant profiles include planting guidelines, maintenance tips, and ideal growing conditions.
- **Real-time Weather Integration:** Integration with a weather API to provide users with real-time weather information relevant to their location.
- **Disease Identification and Plant Feature:** This feature was developed using an external API called Plant.id to identify plant species and diagnose plant diseases based on visual symptoms, which also offers recommendations for the users to treat and prevent these diseases.
- **Community Forum:** A forum where users can engage with each other, and share knowledge, experiences, and tips related to gardening using posts, and interact with likes and replies.
- **Chatting System:** This feature helped users to seek some additional, private help from administrators and experts who manage the application.
- **Marketplace and Ads:** With this feature, users were able to publish any products they wanted to sell with their contact info, making it easier for customers to reach them out and find products, and second-hand products easily with the best prices. Also, simple Ads were added to the application to help others grow their businesses and also help the app grow itself.
- **Task Management and Progress Tracking:** Enable users to manage tasks related to their garden, including watering and pruning. This helps users to track the progress of their plants over time, know about the growth stages, and any issues encountered.
- **Notification and Reminders System:** This system was implemented to notify users about any new messages, post interactions, and announcements, and alert them about their plants’ watering and pruning needs.
- **Admin Dashboard:** This represents a website that enables administrators to manage the overall application data, manage the plants’ dataset, share announcements, manage the community forum, receive any possible reports about inappropriate posts, publish posts and adds, chat with users, and see analytics and Insights about the application.

## Tools Used

- **Visual Studio Code:** It was used as the primary Integrated Development Environment (IDE), for coding, editing, and debugging purposes.
- **GitHub:** Collaboration and version control were facilitated through GitHub, which allowed work organization for us and efficient tracking of code modifications. Also, it was essential for deploying the continuous changes we were making on our server-side to our Amazon server instance and database.
- **Firebase:** For features like real-time reminders and notifications.
- **Pusher:** Which is a simple, scalable and reliable hosted realtime API that we used for chatting.
- **Postman:** It was used for testing different APIs, to ensure the reliability of our application, and make different HTTP requests and responses.
- **Figma:** Which is a platform the UI designers share their projects on, which was really useful to influence us on the design of the app.
- **MySQLWorkbench:** We used this app to manage and fetch our database, perform queries, and connect to our database to test the functionalities during the development process.

## External APIs

- **Open Weather Map API:** For weather data fetching.
- **Plant.id API:** For plants and disease identification.
- **IP Geolocation:** For reaching the location of the users.

  
### Prerequisites

- PHP >= 7.3
- Composer
- MySQL

### Backend Installation

1. **Clone the repository:**

   ```bash
   git clone https://github.com/your-username/farmly-backend.git
   cd farmly-backend
   ```

2. **Install dependencies:**
    ```bash
    composer install
    npm install
    ```

3. **Environment setup:**
   Copy the .env.example file to .env and update the environment variables with your configuration.
    ```bash
    cp .env.example .env
    ```
    Update your .env file with your database credentials, mail settings, and other configurations.

4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```
5. **Run migrations:**
   ```bash
   php artisan migrate
   ```
6. **Seed the database:**
   ```bash
   php artisan db:seed
   ```
7. **Serve the application:**
   ```bash
   php artisan serve
   ```
