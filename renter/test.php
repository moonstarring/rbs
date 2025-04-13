<?php foreach ($formattedProducts as $product): ?>
                            <div class="rounded-3 p-3 bg-body hover-effect height-card">
                                <div class="card-body">
                                    <a href="item.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                        <img src="../img/uploads/<?php echo $product['image']; ?>"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            class="shadow-sm product-image">
                                        <p class="fs-6 mt-2 ms-2 mb-0 fw-bold"><?php echo htmlspecialchars($product['name']); ?></p>
                                    </a>
                                    <div class="d-flex justify-content-between align-items-baseline">
                                        <small class="ms-1 mb-0 text-secondary">
                                            <i class="bi bi-star-fill text-warning me-1"></i>
                                            <?php echo $product['average_rating']; ?> (<?php echo $product['rating_count']; ?>)
                                        </small>
                                        <p class="fs-5 ms-auto mb-0">₱<?php echo $product['rental_price']; ?><small class="text-secondary">/day</small></p>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <form action="add_to_cart.php" method="POST" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                            <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page'] ?? 1); ?>">
                                            <button type="submit" class="btn btn-outline-dark btn-sm rounded-5 shadow-sm">
                                                Add to Cart
                                            </button>
                                        </form>
                                        <a href="/rb/renter/item.php?id=<?= $product['id'] ?>" class="...">Rent Now</a>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>